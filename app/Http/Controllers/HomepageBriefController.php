<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomepageBrief;
use App\Models\Helper\ControllerHelper;
use App\Models\Helper\FileHelper;
use App\Models\Helper\Response;

class HomepageBriefController extends ControllerHelper {
    public function find(Request $request)
    {
        $homepageBrief = HomepageBrief::first();

        if (!$homepageBrief) {
            return response()->json(['message' => 'Homepage brief not found'], 404);
        }

        return response()->json(new Response($request->token, $homepageBrief), 200);
    }

    public function update(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'subtitle' => 'required|string',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $homepageBrief = HomepageBrief::first();

        if (!$homepageBrief) {
            $homepageBrief = new HomepageBrief();
        }

        // Update text fields
        // $homepageBrief->title = $request->input('title');
        // $homepageBrief->subtitle = $request->input('subtitle');
        // $homepageBrief->description = $request->input('description');

        // Handle image upload
        // if ($request->hasFile('image')) {
        //     $image_info = FileHelper::uploadImage($request['image'], 'homepage_brief');
        //     $request['image'] = $image_info['name'];
        // }

        $old_image = $homepageBrief->image;

        $homepageBrief->update($request->all());

        return response()->json(['message' => 'Homepage brief updated successfully', 'data' => $homepageBrief], 200);
    }

    public function upload(Request $request, $id)
    {
        try {
            // Validate the incoming request for image
            $request->validate([
                'photo' => 'required|file|image|mimes:jpg,png,jpeg,webp|max:2048',
            ]);
    
            // Find the HomepageBrief by ID
            $homepageBrief = HomepageBrief::find($id);
    
            if (!$homepageBrief) {
                return response()->json(['message' => ''], 404);
            }
            $old_image = $homepageBrief->image;
            // Handle image upload using FileHelper
            if ($request->hasFile('photo')) {
                $image_info = FileHelper::uploadImage($request['photo'], 'homepage_brief');
                $homepageBrief->image = $image_info['name'];
            }
            if($homepageBrief->save()) {
                if ($old_image) {
                    FileHelper::deleteFile($old_image);
                }
            }
            $home_brief = HomepageBrief::find($id);
            
    
            return response()->json(new Response($request->token, $home_brief));
    
        } catch (\Exception $ex) {
            // Handle exceptions and return an error response
            return response()->json(['message' => '', 'error' => $ex->getMessage()], 500);
        }

    }   
}
