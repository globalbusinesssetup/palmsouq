<?php

namespace App\Http\Controllers;

use App\Models\Helper\ControllerHelper;
use App\Models\Helper\FileHelper;
use App\Models\Helper\Response;
use App\Models\Helper\Utils;
use App\Models\Helper\Validation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Pagination\LengthAwarePaginator;

class ImagesController extends ControllerHelper
{
    public function all(Request $request)
    {
        try {

            if ($can = Utils::userCan($this->user, 'bulk_upload.view')) {
                return $can;
            }

            $lang = $request->header('language');
            $files = [];
            $excludeKeywords = [
                'product', 
                'brand', 
                'category', 
                'thumb',
                'slider',
                'banner',
                'logo',
                'avatar',
                'favicon',
                'default',
                'description'
            ];

            if (config('env.media.STORAGE') == config('env.media.LOCAL')) {
                $directoryPath = FileHelper::getUploadPath();
                $allFiles = Utils::scanDir($directoryPath); // Scan all files in the directory

                // Filter files to exclude keywords
                $files = array_filter($allFiles, function ($file) use ($excludeKeywords) {
                    foreach ($excludeKeywords as $keyword) {
                        if (Str::contains(strtolower($file), strtolower($keyword))) {
                            return false;
                        }
                    }
                    return true;
                });
            } else if (config('env.media.STORAGE') == config('env.media.GCS')) {
                $files = FileHelper::readAllFileGcs(); // For GCS storage
            }

            // Merge filtered files (if needed for additional processing)
            $files = array_merge($files, []);
            // Filter files based on search query if provided
            if ($request->has('q') && !empty($request->q)) {
                $searchQuery = strtolower($request->q);
                $files = array_filter($files, function($file) use ($searchQuery) {
                    return Str::contains(strtolower($file), $searchQuery);
                });
            }

            $page = request()->get('page', 1); // Get the current page (default 1)
            $perPage = Config::get('constants.api.PAGINATION'); // Set items per page

            // Convert array to Laravel Collection
            $filesCollection = collect($files);
            \Log::info($filesCollection);
            // Paginate the collection
            $paginatedFiles = new LengthAwarePaginator(
                $filesCollection->forPage($page, $perPage)->values(), // Slice items for current page
                $filesCollection->count(), // Total count
                $perPage,
                $page, // Current page
                ['path' => request()->url()] // Uses current request URL for pagination
            );

            // Return the response
            return response()->json(new Response($request->token, $paginatedFiles));

        } catch (\Exception $ex) {
            return response()->json(Validation::error($request->token, $ex->getMessage()));
        }
    }




    public function upload(Request $request)
    {
        try {
            
            $validator = Validation::bulk_image_upload($request, 'images');

            if ($validator) {
                return response()->json($validator, 400);
            }
            

            if ($can = Utils::userCan($this->user, 'bulk_upload.edit')) {
                return $can;
            }

             // Optional for large uploads

            $images = [];
            $lang = $request->header('language');

            if ($request->hasFile('images')) {
                
                if (count($request->images) > Config::get('constants.media.MAX_IMG_UPLOAD')) {
                    return response()->json(Validation::error($request->token, __('lang.multi_img', [], $lang), 'multiple_image'));
                }

                foreach ($request->images as $img) {
                    $validate = Validation::multipleImages(['photo' => $img], $request->token);
                    if ($validate) {
                        return response()->json($validate);
                    }

                    // Call the uploadImage method with the original filename
                    $image_info = FileHelper::uploadImage($img, pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME), true, true);

                    array_push($images, $image_info);
                }

                return response()->json(new Response($request->token, $images));
            }

            return response()->json(Validation::error($request->token, __('lang.invalid_parameter', [], $lang), 'multiple_image'));
        } catch (\Exception $ex) {
            return response()->json(Validation::error($request->token, $ex->getMessage(), 'multiple_image'));
        }
    }




    public function delete(Request $request, $image)
    {

        $lang = $request->header('language');

        if ($can = Utils::userCan($this->user, 'bulk_upload.edit')) {
            return $can;
        }


       FileHelper::deleteFile($image);

        return response()->json(new Response($request->token, $image));
    }


}
