<?php

namespace Database\Seeders;

use App\Models\HomepageBrief;
use Illuminate\Database\Seeder;

class HomepageBriefSeeder extends Seeder
{
    public function run()
    {
        HomepageBrief::create([
            'title' => 'Fastest and Cheapest Store in UAE',
            'subtitle' => 'PalmSouq is Revolutionizing the Online Outdoor & Adventure Store.',
            'description' => 'Delivering top-quality Tools and Products in UAE with a wide range of high-quality Tools products at the best prices in the UAE. Our easy-to-use website makes it a breeze to order the printed materials you need—simply select your desired product, choose your quantity, and upload your design. Our team of expert printers will take it from there.',
            'image' => 'homepage_brief_image.jpg', // Path to the uploaded image
        ]);
    }
}
