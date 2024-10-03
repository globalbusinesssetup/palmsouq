<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Models\Helper\ControllerHelper;
use Illuminate\Http\Request;
use App\Models\Helper\Response;
use App\Models\Helper\Utils;
use App\Models\Helper\Validation;
use Illuminate\Support\Facades\Config;

class TestimonialController extends ControllerHelper
{
    // List all testimonials
    public function all(Request $request)
    {
        try {
            $lang = $request->header('language');

            // Assuming you have 'testimonial.view' permission
            if ($can = Utils::userCan($this->user, 'testimonial.view')) {
                return $can;
            }

            $query = Testimonial::query();

            // Default order and type handling, with fallback values if not provided
            $orderby = $request->orderby ?? 'created_at'; // Default to created_at if not provided
            $type = $request->type ?? 'desc'; // Default to descending order

            $query = $query->orderBy($orderby, $type);

            if ($lang) {
                $query = $query->leftJoin('testimonials_langs as trl', function ($join) use ($lang) {
                    $join->on('trl.testimonial_id', '=', 'testimonials.id'); // Adjusted to match table names
                    $join->where('trl.lang', $lang);
                });
                $query = $query->select('testimonials.*', 'trl.detail');

                if ($request->q) {
                    $query = $query->where('trl.detail', 'LIKE', "%{$request->q}%");
                }
            } else {
                if ($request->q) {
                    $query = $query->where('testimonials.testimonial', 'LIKE', "%{$request->q}%");
                }
            }

            // Use constants for pagination, ensuring it's defined
            $data = $query->paginate(Config::get('constants.api.PAGINATION', 15)); // Default pagination to 15 if not set

            foreach ($data as $item) {
                $item['created'] = Utils::formatDate($item->created_at);
            }

            return response()->json(new Response($request->token, $data));

        } catch (\Exception $ex) {
            return response()->json(Validation::error($request->token, $ex->getMessage()));
        }
    }


    // Store a new testimonial
    public function action(Request $request, $id = null)
    {
        try {
            // Validate the incoming request
            $validate = Validation::testimonial($request);
            if ($validate) {
                return response()->json($validate, 422); // Validation failed, return with 422 Unprocessable Entity
            }

            if ($id) {
                // If ID is provided, update the existing testimonial
                $testimonial = Testimonial::find($id);
                if (!$testimonial) {
                    return response()->json([
                        'message' => 'Testimonial not found'
                    ], 404); // Return 404 if testimonial not found
                }

                $testimonial->update($request->only(['client_name', 'testimonial', 'rating', 'status']));

                // Format the updated_at date
                $testimonial->updated = Utils::formatDate($testimonial->updated_at);

                return response()->json([
                    'message' => 'Testimonial updated successfully',
                    'data' => $testimonial
                ], 200); // Return success response with 200 status for update

            } else {
                // If no ID, create a new testimonial
                $testimonial = Testimonial::create($request->only(['client_name', 'testimonial', 'rating', 'status']));

                // Format the created_at date
                $testimonial->created = Utils::formatDate($testimonial->created_at);

                return response()->json([
                    'message' => 'Testimonial created successfully',
                    'data' => $testimonial
                ], 201); // Return success response with 201 status for creation
            }
        } catch (\Exception $ex) {
            // Handle exceptions and return a 500 Internal Server Error with a message
            return response()->json(Validation::error($request->token, $ex->getMessage()), 500);
        }
    }

    // Show a specific testimonial
    public function show(Request $request, $id)
    {
        try {
            // Get the 'language' from the request headers
            $lang = $request->header('language');

            // Check if the user has permission to view the testimonial
            if ($can = Utils::userCan($this->user, 'testimonial.view')) {
                return $can; // If the user doesn't have permission, return the result from userCan
            }

            // Start querying the Testimonial model
            $query = Testimonial::query();

            // If a language is provided, perform a left join with the translation table
            if ($lang) {
                $query = $query->leftJoin('testimonials_langs as trl', function ($join) use ($lang) {
                    $join->on('trl.testimonial_id', '=', 'testimonials.id');
                    $join->where('trl.lang', $lang);
                });
                $query = $query->select('testimonials.*', 'trl.detail');
            }

            // Find the testimonial by ID
            $testimonial = $query->find($id);

            // If the testimonial is not found, return a 'no data' response
            if (is_null($testimonial)) {
                return response()->json(Validation::noDataLang($lang), 404); // 404 Not Found
            }

            // Return the testimonial data in a formatted response
            return response()->json(new Response($request->token, $testimonial));

        } catch (\Exception $ex) {
            // Handle exceptions and return an error response
            return response()->json(Validation::error($request->token, $ex->getMessage()), 500);
        }
    }

    // Delete a testimonial
    public function destroy(Request $request, $id)
    {
        try {
            // Get the 'language' from the request headers
            $lang = $request->header('language');

            // Check if the user has permission to delete the testimonial
            if ($can = Utils::userCan($this->user, 'testimonial.delete')) {
                return $can; // If the user doesn't have permission, return the result from userCan
            }

            // Handle multiple IDs if provided, e.g., "1,2,3"
            $ids = explode(",", $id);

            foreach ($ids as $i) {
                // Find the testimonial by ID
                $testimonial = Testimonial::find($i);

                // If the testimonial is not found, return a 'no data' response
                if (is_null($testimonial)) {
                    return response()->json(Validation::noDataLang($lang), 404); // 404 Not Found
                }

                // Delete any related resources (assuming you have associated images, translations, etc.)
                // Adjust this section based on your related models and how you manage associated data
                // TestimonialTranslation::where('testimonial_id', $i)->delete(); // Example for translation table cleanup
                // FileHelper::deleteFile($testimonial->image); // Example: delete the testimonial's image if applicable

                // Delete the testimonial itself
                $testimonial->delete();
            }

            // Return a successful response indicating the testimonials were deleted
            return response()->json(new Response($request->token, true), 200);

        } catch (\Exception $ex) {
            // Handle exceptions and return an error response
            return response()->json(Validation::error($request->token, $ex->getMessage()), 500);
        }
    }
}

