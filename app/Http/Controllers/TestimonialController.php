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

            if ($can = Utils::userCan($this->user, 'home_slider.view')) {
                return $can;
            }

            $query = Testimonial::query();

            $query = $query->orderBy('testimonial.' . $request->orderby, $request->type);




            if ($lang) {
                $query = $query->leftJoin('testimonials_langs as trl', function ($join) use ($lang) {
                    $join->on('trl.site_feature_id', '=', 'testimonial.id');
                    $join->where('trl.lang', $lang);
                });
                $query = $query->select('testimonials.*', 'trl.detail');


                if ($request->q) {
                    $query = $query->where('trl.detail', 'LIKE', "%{$request->q}%");
                }
            }else {

                if ($request->q) {
                    $query = $query->where('testimonials.testimonial', 'LIKE', "%{$request->q}%");
                }
            }


            $data = $query->paginate(Config::get('constants.api.PAGINATION'));

            foreach ($data as $item) {
                $item['created'] = Utils::formatDate($item->created_at);
            }

            return response()->json(new Response($request->token, $data));


        } catch (\Exception $ex) {
            return response()->json(Validation::error($request->token, $ex->getMessage()));
        }
    }

    // Store a new testimonial
    public function store(Request $request)
    {
        $testimonial = Testimonial::create($request->all());
        return response()->json($testimonial, 201);
    }

    // Show a specific testimonial
    public function show($id)
    {
        return Testimonial::findOrFail($id);
    }

    // Update a testimonial
    public function update(Request $request, $id)
    {
        $testimonial = Testimonial::findOrFail($id);
        $testimonial->update($request->all());
        return response()->json($testimonial, 200);
    }

    // Delete a testimonial
    public function destroy($id)
    {
        Testimonial::destroy($id);
        return response()->json(null, 204);
    }
}

