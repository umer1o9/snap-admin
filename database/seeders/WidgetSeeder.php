<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WidgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
//        DB::table('users')->insert([
        DB::table('widgets')->insert([
            [
                'name' => 'Get Section',
                'code' => 'get_section',
                'label' => 'Get Section',
                'description' => 'Get brief sections based on the given topic and title.',
                'is_active' => 1,
                'type' => 'default',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Get Title',
                'code' => 'get_title',
                'label' => 'Get Title',
                'description' => 'Get titles on a broad SEO topic.',
                'is_active' => 0,
                'type' => 'default',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Sales Copy',
                'code' => 'sales_copy',
                'label' => 'Sales Copy',
                'description' => ' Get a marketing copy for your product or service.',
                'is_active' => 0,
                'type' => 'default',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Co-Write',
                'code' => 'expend_blogpost',
                'label' => 'Co-Write',
                'description' => 'Get more content based on the provided keyword',
                'is_active' => 0,
                'type' => 'default',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'LinkedIn Post',
                'code' => 'linkedin_post',
                'label' => 'LinkedIn Post',
                'description' => ' Get a caption for your LinkedIn post.',
                'is_active' => 0,
                'type' => 'default',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Professional Communication',
                'code' => 'professional_talk',
                'label' => 'Professional Communication',
                'description' => ' Convert your email or chat message into a professionally written text.',
                'is_active' => 0,
                'type' => 'default',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Video Script',
                'code' => 'video_script',
                'label' => 'Video Script',
                'description' => 'Convert short text or pointers into a video script.',
                'is_active' => 0,
                'type' => 'default',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

        ]);
    }
}
