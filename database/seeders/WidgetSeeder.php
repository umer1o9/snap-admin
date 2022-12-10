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
                'category_code' => 'content-writing',
                'description' => 'Get brief sections based on the given topic and title.',
                'is_active' => 1,
                'type' => 'default',
                'allow_voting' => 0,
                'price' => 10,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Get Title',
                'code' => 'get_title',
                'label' => 'Get Title',
                'category_code' => 'content-writing',
                'description' => 'Get titles on a broad SEO topic.',
                'is_active' => 1,
                'type' => 'default',
                'allow_voting' => 1,
                'price' => 10,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Sales Copy',
                'code' => 'sales_copy',
                'label' => 'Sales Copy',
                'category_code' => 'content-writing',
                'description' => ' Get a marketing copy for your product or service.',
                'is_active' => 1,
                'type' => 'default',
                'allow_voting' => 0,
                'price' => 10,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Co-Write',
                'code' => 'expend_blogpost',
                'label' => 'Co-Write',
                'category_code' => 'business-writing',
                'description' => 'Get more content based on the provided keyword',
                'is_active' => 1,
                'type' => 'default',
                'allow_voting' => 0,
                'price' => 10,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'LinkedIn Post',
                'code' => 'linkedin_post',
                'label' => 'LinkedIn Post',
                'category_code' => 'business-writing',
                'description' => ' Get a caption for your LinkedIn post.',
                'is_active' => 1,
                'type' => 'default',
                'allow_voting' => 0,
                'price' => 10,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Professional Communication',
                'code' => 'professional_talk',
                'label' => 'Professional Communication',
                'category_code' => 'content-writing',
                'description' => ' Convert your email or chat message into a professionally written text.',
                'is_active' => 1,
                'type' => 'default',
                'allow_voting' => 0,
                'price' => 10,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Video Script',
                'code' => 'video_script',
                'label' => 'Video Script',
                'category_code' => 'email',
                'description' => 'Convert short text or pointers into a video script.',
                'is_active' => 1,
                'type' => 'default',
                'allow_voting' => 1,
                'price' => 10,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

        ]);
    }
}
