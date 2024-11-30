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
use Stripe\File;

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
                'thumb'
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
            $test = array_merge($files, []);

            // Return the response
            return response()->json(new Response($request->token, $test));
        } catch (\Exception $ex) {
            return response()->json(Validation::error($request->token, $ex->getMessage()));
        }
    }




    public function upload(Request $request)
    {
        try {
            if ($can = Utils::userCan($this->user, 'bulk_upload.edit')) {
                return $can;
            }

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
                    $image_info = FileHelper::uploadImage($img, pathinfo($img->getClientOriginalName(), PATHINFO_FILENAME));

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
