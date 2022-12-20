<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\ActionItem;
use App\Models\ApiModel\ConvertIntoBenefit;
use App\Models\ApiModel\CoWrite;
use App\Models\ApiModel\EasyToRead;
use App\Models\ApiModel\GetTitle;
use App\Models\ApiModel\LinkedinPost;
use App\Models\ApiModel\ProfessionalTalk;
use App\Models\ApiModel\SalesCopy;
use App\Models\ApiModel\VideoScript;
use App\Models\ApiModel\Widget;
use App\Models\GetSection;
use App\Models\ApiModel\WidgetsVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WidgetController extends Controller
{
    //
    public function index(Request $request){

        $code = $request->input('code');
        $category = $request->input('category');

        $allow_voting = $request->input('allow_voting');
        if ($allow_voting){
            $allow_voting = 1;
        }else{
            $allow_voting = 0;
        }
        if ($code){
            $widgets = Widget::where('code', $code)->get();
        }else{
            if (!$category){
                $widgets = Widget::with(['votes'])->where('is_active', 1)->where('allow_voting', $allow_voting)->get();
            }else{
                $widgets = Widget::with(['votes'])->where('category_code', $category)->where('is_active', 1)->where('allow_voting', 0)->get();
            }
        }
        return response()->json(['code' => 200, 'status' => true, 'message' => 'Success', 'data' =>  ['widgets' => $widgets]]);
    }
    //
    public function results(Request $request){
        $user = Auth::user();
        if (!$user){
            response()->json(['code' => 402, 'status' => true, 'message' => 'Please Login', 'data' => []]);

        }
        $code = $request->input('code');
        $search_id = $request->input('search_id');
        $query = null;
        if ($code == 'get_title'){
            $query = GetTitle::where('user_id', $user->id);
        }else if($code == 'get_section'){
            $query = GetSection::where('user_id', $user->id);
        }else if($code == 'sales_copy'){
            $query = SalesCopy::where('user_id', $user->id);
        }else if ($code == 'expend_blogpost'){
            $query = CoWrite::where('user_id', $user->id);
        }else if($code == 'linkedin_post'){
            $query = LinkedinPost::where('user_id', $user->id);
        }else if($code == 'professional_talk'){
            $query = ProfessionalTalk::where('user_id', $user->id);
        }else if ($code == 'video_script'){
            $query = VideoScript::where('user_id', $user->id);
        }else if($code == 'action_item'){
            $query = ActionItem::where('user_id', $user->id);
        }else if($code == 'easy_to_read'){
            $query = EasyToRead::where('user_id', $user->id);
        }else if($code == 'convert_into_benefits'){
            $query = ConvertIntoBenefit::where('user_id', $user->id);
        }

        if ($query == null){
            response()->json(['code' => 402, 'status' => true, 'message' => 'Please provide a valid Code', 'data' => []]);
        }
        if ($search_id){
            $query = $query->where('id', $search_id);
        }else{
            $result = $query->where('valid', 1)->get();
        }

        if ($code == 'expend_blogpost'){
            foreach ($result as $data){
                if ($data->parent_id == null){
                    $child_response = $this->find_last_article($data->id);
                    $data->response = $child_response['response'];
                    $data['child_section_to_expend'] = $child_response['child_section_to_expend'];
                }
            }
        }

        foreach ($result as $key => $data){
            if ($data->response != null){
                $data->response = unserialize($data->response);
            }
        }
        return response()->json(['code' => 200, 'status' => true, 'message' => 'Success', 'data' => $result]);
    }

    public function result(Request $request){
        $user = Auth::user();
        if (!$user){
            response()->json(['code' => 402, 'status' => true, 'message' => 'Please Login', 'data' => []]);

        }
        $code = $request->input('code');

        $query = null;
        if ($code == 'get_title'){
            $query = GetTitle::where('user_id', $user->id);
        }else if($code == 'get_section'){
            $query = GetSection::where('user_id', $user->id);
        }else if($code == 'sales_copy'){
            $query = SalesCopy::where('user_id', $user->id);
        }else if ($code == 'expend_blogpost'){
            $query = CoWrite::where('user_id', $user->id);
        }else if($code == 'linkedin_post'){
            $query = LinkedinPost::where('user_id', $user->id);
        }else if($code == 'professional_talk'){
            $query = ProfessionalTalk::where('user_id', $user->id);
        }else if ($code == 'video_script'){
            $query = VideoScript::where('user_id', $user->id);
        }else if($code == 'action_item'){
            $query = ActionItem::where('user_id', $user->id);
        }else if($code == 'easy_to_read'){
            $query = EasyToRead::where('user_id', $user->id);
        }else if($code == 'convert_into_benefits'){
            $query = ConvertIntoBenefit::where('user_id', $user->id);
        }

        if ($query == null){
            response()->json(['code' => 402, 'status' => true, 'message' => 'Please provide a valid Code', 'data' => []]);
        }
        $result = $query->where('valid', 1)->get();

        if ($code == 'expend_blogpost'){
            foreach ($result as $data){
                if ($data->parent_id == null){
                    $child_response = $this->find_last_article($data->id);
                    $data->response = $child_response['response'];
                    $data['child_section_to_expend'] = $child_response['child_section_to_expend'];
                }
            }
        }

        foreach ($result as $key => $data){
            if ($data->response != null){
                $data->response = unserialize($data->response);
            }
        }
        return response()->json(['code' => 200, 'status' => true, 'message' => 'Success', 'data' => $result]);
    }

    public function find_last_article($next_parent_id){
        $current_co_write = CoWrite::find($next_parent_id);
        $child_co_write = CoWrite::where('parent_id', $next_parent_id)->first();
        if ($child_co_write != null){
            return $this->find_last_article($child_co_write->id);
        }
//        dd($current_co_write->request);
//        dd(unserialize($current_co_write->request));
        return ['response' => $current_co_write->response, 'child_section_to_expend' => $current_co_write->section_to_expend ];
    }
    public function get_categories(){
        $widgets = Widget::select('category_code')->distinct()->get();
        $result = [];
        foreach ($widgets as $widget){
            $result[] = $widget->category_code;
        }
        return response()->json(['code' => 200, 'status' => true, 'message' => 'Success', 'data' => ['categories' => $result]]);

    }
    public function vote_for_widget(Request $request){
        $user = Auth::user();
        $widget_id = $request->widget_id;
        $widgets_votes = new WidgetsVote();
        $widgets_votes->user_id = $user->id;
        $widgets_votes->widget_id = $widget_id;
        $widgets_votes->save();
        return response()->json(['code' => 200, 'status' => true, 'message' => 'Vote submitted Successfully', 'data' =>  []]);
    }
}
