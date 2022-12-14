<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\AllowedSearch;
use App\Models\ApiModel\PaymentMethod;
use App\Models\ApiModel\Plan;
use App\Models\ApiModel\Sales;
use App\Models\ApiModel\SalesCopy;
use App\Models\ApiModel\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentMethodController extends Controller
{
    //
    public function index(){

        $payment_methods = PaymentMethod::where('is_active', 1)->get();
        return response()->json(['code' => 200, 'status' => true, 'message' => 'Success', 'data' => ['payment_methods' => $payment_methods]]);
    }

    public function register_plan(Request $request){

        $validated = $request->validate([
            'plan_type' => ['required', 'string'],
            'selected_widgets' => ['required'],
            'total_count' => ['required'],
            'total_price' => ['required'],
            'payment_method' => ['required'],
            'remarks' => ['required'],
        ]);
        DB::beginTransaction();
        try {
            $user = Auth::user();
            //TODO: create Plan first
            $plan = new Plan();
            $plan->name = $request->plan_type;
            $plan->no_of_allowed_searches = $request->total_count;
            $plan->words = $request->total_words;
            $plan->status = 0; //TODO: need to develop auto Active -> Default Unselected for now
            $plan->price = $request->total_price;
            $plan->currency = 'PKR';
            $plan->code = 'custom-plan';
            $plan->description = 'PAID PLAN, plan Buy by user';
            $plan->type = 'paid';
            $plan->save();
            //TODO: Entry in Allowed Search Table must be a another function
            $allowed_search = $this->add_allowed_search($request->selected_widgets, $plan->id, $request->plan_type, $user->id);
            //TODO: create Sale With plan ID

            $sale = new Sales();
            $sale->user_id = $user->id;
            $sale->plan_id = $plan->id;
            $sale->payment_method = $request->payment_method;
            $sale->remarks = $request->remarks;
            if ($request->transaction_id){
                $sale->transaction_id = $request->transaction_id;
            }
            if ($request->company_id){
                $sale->company_id = $request->company_id;
            }
            $sale->save();

            $allowed_search->sale_id = $sale->id;
            $allowed_search->save();
            DB::commit();
            return response()->json(['code' => 200 , 'status' => true ,'message' => 'Plan added successfully, will be active in 12Hours']);

        }catch (\Exception $ex) {
            return response()->json(['code' => 422 , 'status' => false ,'message' => $ex->getMessage()]);
        }
    }

    private function add_allowed_search($widgets, $plan_id, $plan_type, $user_id){
        $allowed_searches = new AllowedSearch();
        $allowed_searches->user_id = $user_id;
        $allowed_searches->sale_id = 0;

        $allowed_searches->get_section = 0;
        $allowed_searches->get_title = 0;
        $allowed_searches->expend_blogpost = 0;
        $allowed_searches->video_script = 0;
        $allowed_searches->sales_copy = 0;
        $allowed_searches->improve_headline = 0;
        $allowed_searches->suggest_headline = 0;
        $allowed_searches->brain_stormer = 0;
        $allowed_searches->action_item = 0;
        $allowed_searches->easy_to_read = 0;
        $allowed_searches->linkedin_post = 0;
        $allowed_searches->professional_talk = 0;
        $allowed_searches->save();
        if ($plan_type != 'all_widgets'){
            foreach (json_decode($widgets) as $widget){
                $db_widget = Widget::find($widget->widget_id);
                if ($db_widget){
                    $code = $db_widget->code;
                    $allowed_searches->$code = $widget->qty;
                }
            }
        }
        $allowed_searches->save();
        return $allowed_searches;
    }

    public function sales(){
        $user = Auth::user();
        $sales = Sales::with('plans')->where('user_id', $user->id)->get();
        return response()->json(['code' => 200, 'status' => true, 'message' => 'Success', 'data' => ['sales' => $sales]]);

    }
}
