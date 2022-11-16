<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\AllowedSearch;
use App\Models\ApiModel\LoginHistory;
use App\Models\ApiModel\Plan;
use App\Models\ApiModel\Sales;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    function customer_login(Request $request){
        return response()->json($this->login($request->toArray()));
    }
// Function to get the client IP address
    function get_client_ip() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    function login($request)
    {

        $response = ['code' => 402, 'status' => false, 'message' => '', 'data' => []];
        $credentials = ['email' => $request['email'], 'password' => $request['password']];

        if (!Auth::attempt($credentials)) {
            $response['message'] =  'email or password incorrect';
            return response()->json($response);
        }
        $user = Auth::user();
        $accessToken = $user->createToken('accessToken')->plainTextToken;
        //Store Login History
        $login_history = new LoginHistory();
        $login_history->user_id = $user->id;
        $login_history->ip = $this->get_client_ip();
        $login_history->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $login_history->save();

        //save user fcm token
        $response['status'] = true;
        $response['code'] = 200;
        $response['message'] = 'Login Successfully';
        $response['data'] = ['user' => $user, 'token' => $accessToken];
        return $response;
    }

    function register(Request $request)
    {

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required'],
            'i_want_for' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        DB::beginTransaction();
        try {
            $user = Auth::user();

            if (!$user){
                $user = new User();
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->email = $request->email;
                $user->phone = $request->phone;
                $user->dob = $request->dob;
                $user->cnic = $request->cnic;
                $user->for = $request->i_want_for;
                $user->country_id = $request->country;
                $user->password = Hash::make($request->password);
                $user->save();
            }
//            dump($user);
            if ($user){
                $free_plan = Plan::where('code', 'signin-plan')->first();
                $sale = new Sales();
                $sale->user_id = $user->id;
                $sale->plan_id = $free_plan->id;
                $sale->payment_method = 'signup';
                $sale->remarks = 'Get free plan using Signup';
                $sale->save();

                $allowed_searches = new AllowedSearch();
                $allowed_searches->user_id = $user->id;
                $allowed_searches->sale_id = $sale->id;
                $allowed_searches->save();
            }
            $response = $this->login($request->toArray());

            if ($response['code'] == 200){
                $response['message'] = "Register Successfully";
            }
            DB::commit();
            return response()->json($response);
        }
        catch (\Exception $ex) {
            return response()->json(['code' => 422 , 'status' => false ,'message' => $ex->getMessage()]);
        }
    }

    function get_user_detail(){
        $user = Auth::user();
        $response = ['status' => true, 'code' => 402, 'message' => '', 'data' => []];
        $user_detail = User::with(['allowed_searches', 'sales', 'consumed_searches'])->find($user->id);
//        $user_detail = User::with(['allowed_searches' => function($query){
//            $query->where( );
//        }, 'sales', 'consumed_searches'])->find($user->id);
        if ($user_detail){
            $response = ['status' => false, 'code' => 200, 'message' => '', 'data' => ['user' => $user_detail]];
        }
        return response()->json($response);

    }
}
