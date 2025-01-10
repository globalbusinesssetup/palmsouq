<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HomepageBrief;

class HomepageBriefController extends Controller {
    public function find(): JsonResponse
    {
        $homepageBrief = HomepageBrief::first();

        if (!$homepageBrief) {
            return response()->json(['message' => 'Homepage brief not found'], 404);
        }

        return response()->json([
            'title' => $homepageBrief->title,
            'subtitle' => $homepageBrief->subtitle,
            'description' => $homepageBrief->description,
            'image_url' => asset($homepageBrief->image),
        ], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);

        $homepageBrief = HomepageBrief::first();

        if (!$homepageBrief) {
            $homepageBrief = new HomepageBrief();
        }

        // Update text fields
        $homepageBrief->title = $request->input('title');
        $homepageBrief->subtitle = $request->input('subtitle');
        $homepageBrief->description = $request->input('description');

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($homepageBrief->image && Storage::exists('public/' . $homepageBrief->image)) {
                Storage::delete('public/' . $homepageBrief->image);
            }

            // Store the new image
            $imagePath = $request->file('image')->store('uploads', 'public');
            $homepageBrief->image = $imagePath;
        }

        $homepageBrief->save();

        return response()->json(['message' => 'Homepage brief updated successfully', 'data' => $homepageBrief], 200);
    }

    public function upload(Request $request, $id = null)
    {
        try {
            // Validate the incoming request for image
            $request->validate([
                'image' => 'required|image|mimes:jpg,png,jpeg|max:2048',
            ]);
    
            // Find the HomepageBrief by ID
            $homepageBrief = HomepageBrief::find($id);
    
            if (!$homepageBrief) {
                return response()->json(['message' => 'Homepage brief not found'], 404);
            }
    
            // Handle image upload using FileHelper
            if ($request->hasFile('image')) {
                $image_info = FileHelper::uploadImage($request['image'], 'homepage_brief');
                $request['image'] = $image_info['name'];
            }
    
            $old_image = $homepageBrief->image;
    
            if($homepageBrief->update($request->all())) {
                if ($old_image) {
                    FileHelper::deleteFile($old_image);
                }
            }
    
            $home_brief = HomepageBrief::find($id);
    
    
            return response()->json(new Response($request->token, $home_brief));
    
        } catch (\Exception $ex) {
            // Handle exceptions and return an error response
            return response()->json(['message' => 'An error occurred', 'error' => $ex->getMessage()], 500);
        }

    }   
}
