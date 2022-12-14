<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\AllowedSearch;
use App\Models\ApiModel\ConsumedSearchHistory;
use App\Models\ApiModel\ContactUs;
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
//            'first_name' => ['required', 'string', 'max:255'],
            //'last_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
//            'phone' => ['required'],
//            'i_want_for' => ['required'],
            'password' => ['required', 'string', 'min:8'], //, 'confirmed'
        ]);
        DB::beginTransaction();
        try {
            $user = Auth::user();

            if (!$user){
                $user = new User();
                $user->first_name = $request->name;
                $user->last_name = $request->name;
                $user->email = $request->email;
//                $user->phone = $request->phone;
//                $user->dob = $request->dob;
//                $user->cnic = $request->cnic;
//                $user->for = $request->i_want_for;
//                $user->country_id = $request->country;
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

        $sales = Sales::with(['plans'=> function($q){
            $q->where('status', 1);
        }, 'allowed_searches'])->where('user_id', $user->id)->get();
        $no_of_searches = 0;
        $no_of_all_searches = 0;
        $custom_plan_ids = [];
        $all_plan_ids = [];
        foreach ($sales as $sale){
            if ($sale->plans){
                if ($sale->plans->name == 'all_widgets'){
                    $no_of_all_searches += $sale->plans->no_of_allowed_searches;
                    $all_plan_ids[] = $sale->allowed_searches->id;
                }else{
                    $no_of_searches += $sale->plans->no_of_allowed_searches;
                    $custom_plan_ids[] = $sale->allowed_searches->id;
                }
            }
        }

        $user_detail['sales'] = $sales;
        $user_detail['no_of_allowed_searches'] = $no_of_searches;
        $user_detail['no_of_all_searches'] = $no_of_all_searches;

        $user_detail['get_section'] = 0;
        $user_detail['get_title'] = 0;
        $user_detail['expend_blogpost'] = 0;
        $user_detail['video_script'] = 0;
        $user_detail['linkedin_post'] = 0;
        $user_detail['sales_copy'] = 0;
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
            $user_detail['sales_copy'] += $allowed_search->sales_copy;
            $user_detail['improve_headline'] += $allowed_search->improve_headline;
            $user_detail['brain_stormer'] += $allowed_search->brain_stormer;
            $user_detail['action_item'] += $allowed_search->action_item;
            $user_detail['easy_to_read'] += $allowed_search->easy_to_read;
            $user_detail['professional_talk'] += $allowed_search->professional_talk;
        }
        $consumed_searches = ConsumedSearchHistory::select('widget_code', DB::raw('count(*) as total'))->where('user_id', $user->id)
            ->whereIn('allowed_search_id', $custom_plan_ids)->groupBy('widget_code')->get();


        $consumed_all_searches = ConsumedSearchHistory::select(DB::raw('count(*) as total'))->where('user_id', $user->id)
            ->whereIn('allowed_search_id', $all_plan_ids)->get();

        $user_detail['consumed_searches'] = $consumed_searches;
        $user_detail['consumed_all_searches'] = $consumed_all_searches;
        if ($user_detail){
            $response = ['status' => false, 'code' => 200, 'message' => '', 'data' => ['user' => $user_detail]];
        }
        return response()->json($response);
    }

    function update_profile(Request $request)
    {
        $user = Auth::user();
        $user->first_name = $request->name;
        $user->last_name = $request->name;
        $user->phone = $request->phone;
        $user->dob = $request->dob;
        $user->cnic = $request->cnic;
        $user->profession = $request->profession;
        if ($user->save()){
            $response = ['status' => true, 'code' => 200, 'message' => 'Profile update successfully', 'data' => $user];
        }else{
            $response = ['status' => false, 'code' => 402, 'message' => 'Profile update error', 'data' => []];
        }
        return response()->json($response);
    }

    function update_password(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'], //, 'confirmed'
        ]);

        $user = Auth::user();
        if ($request->password != $request->confirm_password){
            return response()->json(['status' => false, 'code' => 402, 'message' => 'Password and Confirm Password not match', 'data' => []]);
        }
        $user->password = Hash::make($request->password);
        if ($user->save()){
            $response = ['status' => true, 'code' => 200, 'message' => 'Password update successfully', 'data' => $user];
        }else{
            $response = ['status' => false, 'code' => 402, 'message' => 'Password update error', 'data' => []];
        }
        return response()->json($response);
    }

    public function contact_us(Request $request){
        $contact_us = new ContactUs();
        $contact_us->name = $request->name;
        $contact_us->email = $request->email;
        $contact_us->message = $request->message;
        $contact_us->save();
        $response = ['status' => true, 'code' => 200, 'message' => 'Thanks '.$request->name.', Your Request submitted successfully', 'data' => []];
        return response()->json($response);
    }
}


//, SUM(allowed_searches.get_title) as get_title, SUM(allowed_searches.expend_blogpost) as co_write, SUM(allowed_searches.video_script) as video_script, SUM(allowed_searches.linkedin_post) as linkedin_post, SUM(allowed_searches.sales_copies) as sales_copies, SUM(allowed_searches.sales_copies) as sales_copies, SUM(allowed_searches.suggest_headline) as suggest_headline, SUM(allowed_searches.brain_stormer) as brain_stormer, SUM(allowed_searches.action_item) as action_item, SUM(allowed_searches.easy_to_read) as easy_to_read, SUM(allowed_searches.professional_talk) as professional_talk"

//'consumed_searches'=> function($query, $user){
//    $query->select(DB::raw('count(*) as total')->groupBy('widget_id')->where('user_id', $user->id));
//}
