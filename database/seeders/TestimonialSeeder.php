<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $banners = [
            [
                'id' => 1,
                'client_name' => 'John Doe',
                'testimonial' => 'Amet minim mollit non deserunt ullamco est sit aliqua dolor do amet sint. Velit officia consequat duis enim velit mollit. Exercitation veniam consequat sunt nostrud amet.',
                'rating' => 4.5,
                'status' => Config::get('constants.status.PUBLIC'),
            ],
            [
                'id' => 2,
                'client_name' => 'Abdus Salam',
                'testimonial' => 'Amet minim mollit non deserunt ullamco est sit aliqua dolor do amet sint. Velit officia consequat duis enim velit mollit. Exercitation veniam consequat sunt nostrud amet.',
                'rating' => 5,
                'status' => Config::get('constants.status.PUBLIC'),
            ],
            [
                'id' => 3,
                'client_name' => 'Karin Lopa',
                'testimonial' => 'Amet minim mollit non deserunt ullamco est sit aliqua dolor do amet sint. Velit officia consequat duis enim velit mollit. Exercitation veniam consequat sunt nostrud amet.',
                'rating' => 4,
                'status' => Config::get('constants.status.PUBLIC'),
            ],
            [
                'id' => 4,
                'client_name' => 'Floyd Miles',
                'testimonial' => 'Amet minim mollit non deserunt ullamco est sit aliqua dolor do amet sint. Velit officia consequat duis enim velit mollit. Exercitation veniam consequat sunt nostrud amet.',
                'rating' => 4.2,
                'status' => Config::get('constants.status.PUBLIC'),
            ],
            [
                'id' => 5,
                'client_name' => 'Miles Low',
                'testimonial' => 'Amet minim mollit non deserunt ullamco est sit aliqua dolor do amet sint. Velit officia consequat duis enim velit mollit. Exercitation veniam consequat sunt nostrud amet.',
                'rating' => 4.5,
                'status' => Config::get('constants.status.PUBLIC'),
            ],
        ];

        if(!Testimonial::first()){
            foreach ($banners as $i) {
                Testimonial::create($i);
            }
        }
    }
}
