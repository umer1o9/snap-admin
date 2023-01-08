<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\CoWrite;
use App\Models\ApiModel\SalesCopy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoWriteController extends Controller
{
    //
    private $widget_code = 'expend_blogpost';

    public function co_writing(Request $request){
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $co_write = new CoWrite();
        $co_write->request = serialize($request->toArray());
        $co_write->user_id = $user->id;
        $co_write->section_to_expend = $request->section_to_expend;
        $co_write->keywords = $request->keywords;
        $co_write->creativity = $request->creativity;

        if (isset($request->parent_id) && $request->parent_id != null){
            $co_write->parent_id = $request->parent_id;
        }
        $co_write->save();
        $validated = $request->validate([
            'section_to_expend' => ['required', 'string'],
            'keywords' => ['required', 'string', 'max:255'],
            'creativity' => ['required', 'string', 'max:255'],
        ]);
        $allowed_searches =  check_consumed_searches($this->widget_code, $user->id);
        $allowed_searches = json_decode($allowed_searches);
        if ($allowed_searches->status == false){
            $response['message'] = 'Search limit reached.';
            $response['description'] = 'You have hit your search limit and exhausted free searches quota also. To continue, Buy your search quota';
            if ($allowed_searches->allowed_search_id != null){
                update_consume_search_status($allowed_searches);
            }
            return response()->json($response);
        }
        //Main Body
        try{
            DB::beginTransaction();
            $moderation_response = text_moderation($request->section_to_expend);
            if ($moderation_response['code'] == 200) {
                $request_data = $this->create_request($request->toArray());
                $request_data['user'] = (String)$user->id;
                $competition = competition_open_ai($request_data);
                if ($competition['code'] =! 200){
                    return response()->json($response);
                }
                // Complete  : Store Response
                $co_write->response = serialize($competition['response']);
                $co_write->valid = 1;
                $co_write->save();

                // TODO : Update Consumed history
                update_consumed_search_history($this->widget_code, $user->id, $allowed_searches);
                // TODO : Return Success
                $response = ['request_data' => $request_data, 'code' => 200, 'status' => true, 'message' => 'Success', 'id' => $co_write->id, 'parent_id' => $co_write->parent_id , 'data' =>  json_decode($competition['response'])->choices ];
                DB::commit();
                return response()->json($response);
            }else{
                $response['message'] = 'Moderation Error';
                $response['description'] = 'Please Select valid Keywords.';
                return response()->json($response);
            }
        }
        catch (\Exception $ex) {
            $message = $ex->getMessage();
            $message = 'That model is currently overloaded with other requests. You can retry your request, or contact to Admin';
            if ($ex->getCode() == 429 || $ex->getCode() == 503){
                $message = 'That model is currently overloaded with other requests. You can retry your request, or contact to Admin';
            }
            return response()->json(['code' => $ex->getCode() , 'status' => false ,'message' => $ex->getMessage()]);
        }
    }

    public function create_request($data){
        $creativity = $this->creativity($data['creativity']);

        if (isset($data['new_keywords'])){
            $question =  "Based on the ".$data['section_to_expend'].", expand the following using these exact keywords:\n\nExact Keywords:\n".$data['keywords']."\n\nSection to expand:\n".$data['new_keywords']."\n";
        }else{
            $question = "Write a blog section based on [Provided Text] using these [Exact Keywords].\n\n[Exact Keywords]:\n" . $data['keywords'] . "\n\n[Provided Text]:\n" . $data['section_to_expend'] . "\n\n";
        }

        return [
            "prompt"=> $question,
            "temperature"=> $creativity['temperature'],
            "max_tokens"=> 120,
            "top_p"=> 1,
            "stop"=> " \nEND",
            "logprobs"=> 5,
            "frequency_penalty"=> $creativity['frequency_penalty'],
            "presence_penalty"=> $creativity['presence_penalty'],
        ];
    }

    private function creativity($creativity){
        $creativity = [];

        if ($creativity == 'less') {
            $creativity = [  "temperature"  => 0.2, "frequency_penalty" => 0.5, "presence_penalty" => 0.6];
        }elseif ($creativity == 'more'){
            $creativity = [ "temperature"  => 0.7, "frequency_penalty" => 1.5, "presence_penalty" => 1.6];
        }elseif($creativity == 'outrageous'){
            $creativity = [ "temperature"  => 1, "frequency_penalty" => 1.8, "presence_penalty" => 1.9 ];
        }else{
            $creativity = [ "temperature"=> 0.5, "frequency_penalty"=> 1, "presence_penalty"=> 1.2];
        }
        return $creativity;
    }
}
