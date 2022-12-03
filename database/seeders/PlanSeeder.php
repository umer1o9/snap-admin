<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('plans')->insert([
            [
                'name' => 'free',
                'no_of_allowed_searches' => '15',
                'status' => '1',
                'price' => '0',
                'currency' => 'PKR',
                'code' => 'signin-plan',
                'description' => 'free plan for new users.',
                'type' => 'free',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ]);
    }
}
