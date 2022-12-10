<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethod extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('payment_methods')->insert([
           [
               'name' => 'Jazz Cash',
               'key' => 'jazz_cash',
               'icon' => 'https://cdn6.aptoide.com/imgs/a/5/8/a5894713cdf4d74c51622a8d1e453e04_icon.png',
               'is_active' => 1
           ],
            [
               'name' => 'Online Deposit',
               'key' => 'online_deposit',
               'icon' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTyOcoWS2unMqY8xhqdyuC7oiveC7MIjdSWAg&usqp=CAU',
               'is_active' => 1
           ],
            [
               'name' => 'Fiverr',
               'key' => 'fiverr',
               'icon' => 'https://seeklogo.com/images/F/fiverr-new-2020-logo-354E8A08FD-seeklogo.com.png',
               'is_active' => 1
           ],
        ]);
    }
}
