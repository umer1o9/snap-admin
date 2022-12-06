<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    //
    public function index(){
        $payment_methods = PaymentMethod::where('is_active', 1)->get();
        return response()->json(['code' => 200, 'status' => true, 'message' => 'Success', 'data' => ['payment_methods' => $payment_methods]]);
    }
}
