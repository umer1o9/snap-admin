<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\ActionItem;
use App\Models\ApiModel\BrainIdea;
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
use App\Models\User;
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
        }else if($code == 'brain_stormer'){
            $query = BrainIdea::where('user_id', $user->id);
        }

        if ($query == null){
            response()->json(['code' => 402, 'status' => true, 'message' => 'Please provide a valid Code', 'data' => []]);
        }
        if ($search_id){
            $query = $query->where('id', $search_id);
        }
            $result = $query->where('valid', 1)->get();

        if ($code == 'expend_blogpost'){
            foreach ($result as $key => $data){
                   $exists = $this->is_exists($data->id, $result);
                    if ($exists == true){
                        unset($result[$key]);
                    }
            }
        }

        $results = [];
        foreach ($result as $key => $data){

            if ($data->response != null){
                $data->response = unserialize($data->response);
            }
            $data->request = unserialize($data->request);
            $results[] = $data;
        }

        return response()->json(['code' => 200, 'status' => true, 'message' => 'Success', 'data' => $results]);
    }
    public function is_exists($id, $result){
        foreach ($result as $data){
            if ($data->parent_id == $id){
                return true;
            }
        }
        return false;
    }
    public function result(Request $request){
        $code = $request->input('code');
        $result_id = $request->input('result_id');
        $user_id = $request->input('user_id');

        $query = null;
        if ($code == 'get-title'){
            $query = GetTitle::where('id', $result_id);
        }else if($code == 'get_section' || $code == 'get-section'){
            $query = GetSection::where('id', $result_id);
        }else if($code == 'sales_copy' || $code == 'sales-copy'){
            $query = SalesCopy::where('id', $result_id);
        }else if ($code == 'expend_blogpost' || $code == 'expend-blogpost'){
            $query = CoWrite::where('id', $result_id);
        }else if($code == 'linkedin_post' || $code == 'linkedin-post'){
            $query = LinkedinPost::where('id', $result_id);
        }else if($code == 'professional_talk' || $code == 'professional-talk'){
            $query = ProfessionalTalk::where('id', $result_id);
        }else if ($code == 'video_script' || $code == 'video-script'){
            $query = VideoScript::where('id', $result_id);
        }else if($code == 'action_item' || $code == 'action-item'){
            $query = ActionItem::where('id', $result_id);
        }else if($code == 'easy_to_read' || $code == 'easy-to-read'){
            $query = EasyToRead::where('id', $result_id);
        }else if($code == 'convert_into_benefits' || $code == 'convert-into-benefits'){
            $query = ConvertIntoBenefit::where('user_id', $user->id);
        }

        $result = $query->first();
        $result->request = unserialize($result->request);
        $result->response = unserialize($result->response);
        $widget_user = User::find($result->user_id);
        $result['user_name'] = $widget_user->first_name;
        if ($user_id != $result->user_id){
            if ($result->is_public == 0){
                return response()->json(['code' => 404, 'status' => false, 'message' => 'This Content is not Public', 'data' => []]);
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

    public function compilation(Request $request){

        $moderation_response = text_moderation($request->text);
        if ($moderation_response['code'] != 200) {
            $response = ['code' => 422, 'status' => false, 'message' => 'This Text is not allowed', 'data' =>  []];
            return response()->json($response);
        }

        $request_data = [
            "prompt" => $request->text,
            "temperature" => (integer)$request->configuration[0],
            "max_tokens" => (integer)$request->configuration[1],
            "n" => (integer)$request->configuration[2],
            "best_of" => (integer)$request->configuration[3],
            "logprobs" => (integer)$request->configuration[4],
            "frequency_penalty" => (integer)$request->configuration[5],
            "presence_penalty" => (integer)$request->configuration[6],
            "top_p" => (integer)$request->configuration[7],
            "user" => "7",
        ];

        $competition = competition_open_ai($request_data);
        if ($competition['code'] == 200){
            return response()->json(['code' => 200, 'status'=> true , 'message' => 'Success', 'data' => json_decode($competition['response'])->choices]);
        }else{
            $response = ['code' => 422, 'status' => false, 'message' => 'Error', 'data' =>  []];

        }
    }
}
