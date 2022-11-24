<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\AllowedSearch;
use App\Models\ApiModel\ConsumedSearchHistory;
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
        $user_detail = User::with(['allowed_searches'])->find($user->id);

        $user_detail['get_section'] = 0;
        $user_detail['get_title'] = 0;
        $user_detail['expend_blogpost'] = 0;
        $user_detail['video_script'] = 0;
        $user_detail['linkedin_post'] = 0;
        $user_detail['sales_copies'] = 0;
        $user_detail['improve_headline'] = 0;
        $user_detail['suggest_headline'] = 0;
        $user_detail['brain_stormer'] = 0;
        $user_detail['action_item'] = 0;
        $user_detail['professional_talk'] = 0;

        foreach ($user_detail->allowed_searches as $allowed_search){
            $user_detail['get_section'] += $allowed_search->get_section;
            $user_detail['get_title'] += $allowed_search->get_title;
            $user_detail['expend_blogpost'] += $allowed_search->expend_blogpost;
            $user_detail['video_script'] += $allowed_search->video_script;
            $user_detail['linkedin_post'] += $allowed_search->linkedin_post;
            $user_detail['sales_copies'] += $allowed_search->sales_copies;
            $user_detail['improve_headline'] += $allowed_search->improve_headline;
            $user_detail['brain_stormer'] += $allowed_search->brain_stormer;
            $user_detail['action_item'] += $allowed_search->action_item;
            $user_detail['easy_to_read'] += $allowed_search->easy_to_read;
            $user_detail['professional_talk'] += $allowed_search->professional_talk;
        }
        $consumed_searches = ConsumedSearchHistory::select('widget_code', DB::raw('count(*) as total'))->where('user_id', $user->id)->groupBy('widget_code')->get();
        $user_detail['consumed_searches'] = $consumed_searches;
        if ($user_detail){
            $response = ['status' => false, 'code' => 200, 'message' => '', 'data' => ['user' => $user_detail]];
        }
        return response()->json($response);
    }
}
//, SUM(allowed_searches.get_title) as get_title, SUM(allowed_searches.expend_blogpost) as co_write, SUM(allowed_searches.video_script) as video_script, SUM(allowed_searches.linkedin_post) as linkedin_post, SUM(allowed_searches.sales_copies) as sales_copies, SUM(allowed_searches.sales_copies) as sales_copies, SUM(allowed_searches.suggest_headline) as suggest_headline, SUM(allowed_searches.brain_stormer) as brain_stormer, SUM(allowed_searches.action_item) as action_item, SUM(allowed_searches.easy_to_read) as easy_to_read, SUM(allowed_searches.professional_talk) as professional_talk"

//'consumed_searches'=> function($query, $user){
//    $query->select(DB::raw('count(*) as total')->groupBy('widget_id')->where('user_id', $user->id));
//}
