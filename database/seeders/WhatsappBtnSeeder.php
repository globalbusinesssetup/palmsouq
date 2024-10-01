<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Store;
use Illuminate\Database\Seeder;

class WhatsappBtnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            [
                'id' => 1,
                'whatsapp_btn' => true,
                'whatsapp_number' => '1234567890',
                'whatsapp_default_msg' => 'I have a question',
            ],

            [
                'id' => 2,
                'whatsapp_btn' => true,
                'whatsapp_number' => '1234567891',
                'whatsapp_default_msg' => 'I have a question',
            ]
        ];

        $storeId = Store::where('id', 1)->first();
        $storeId2 = Store::where('id', 2)->first();

        if(Store::first() && $storeId && $storeId2){
            foreach ($items as $i) {
                Store::where('id', $i['id'])->update([
                    'whatsapp_btn' => $i['whatsapp_btn'],
                    'whatsapp_number' => $i['whatsapp_number'],
                    'whatsapp_default_msg' => $i['whatsapp_default_msg']
                ]);
            }
        }

    }
}
