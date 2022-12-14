<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\SalesCopy;
use App\Models\ApiModel\VideoScript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VideoScriptController extends Controller
{
    private $widget_code = 'video_script';
    private $number_of_choices = 3;

    //
    public function index(Request $request){
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $video_script = new VideoScript();
        $video_script->request = serialize($request->toArray());
        $video_script->user_id = $user->id;
        $video_script->product_description = $request->product_description;
        $video_script->mood = $request->mood;
        $video_script->structure = $request->structure;
        $video_script->save();
        $validated = $request->validate([
            'product_description' => ['required', 'string'],
            'mood' => ['required', 'string', 'max:255'],
            'structure' => ['required', 'string', 'max:255'],
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
            $moderation_response = text_moderation($request->product_description);
            if ($moderation_response['code'] == 200) {
                $request_data = $this->create_request($request->toArray());
                $request_data['user'] = (String)$user->id;
                $competition = competition_open_ai($request_data);
                if ($competition['code'] =! 200){
                    return response()->json($response);
                }
                // Complete  : Store Response
                $video_script->response = serialize($competition['response']);
                $video_script->valid = 1;
                $video_script->save();

                // TODO : Update Consumed history
                update_consumed_search_history($this->widget_code, $user->id, $allowed_searches);

                // TODO : Return Success
                $response = ['code' => 200, 'status' => true, 'message' => 'Success', 'data' =>  json_decode($competition['response'])->choices ];
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
            if ($ex->getCode() == 429 || $ex->getCode() == 503) {
                $message = 'That model is currently overloaded with other requests. You can retry your request, or contact to Admin';
            }
            return response()->json(['code' => $ex->getCode(), 'status' => false, 'message' => $message]);
        }
    }

    public function create_request($data){
        $creativity = $this->creativity(null);

        if ($data['structure'] == 'story'){
            $question = "Write a creatively " . $data['structure'] . " story entirely about:\n\n" . $data['product_description'] . "\n\n";

        }else if($data['structure'] == 'event'){
            $question = "Write a " . $data['structure'] . " video script for an event using this information:\n\n" . $data['product_description'] . "\n\n";

        }else if($data['structure'] == 'interview'){
            $question = "This is a " . $data['structure'] . " transcript of the conversation about:\n\n" . $data['product_description'] . "\n\n";
        }else{
            $question = "Write " . $data['structure'] . " video script in " . $data['mood'] . " style using this information:\n\n" . $data['product_description'] . "\n\n";

        }
        return [
            "prompt"=> $question,
            "temperature"=> $creativity['temperature'],
            "max_tokens"=> 750,
            "top_p"=> 1,
            "n" => 3,
            "best_of" => 6,
            "logprobs"=> 5,
            "frequency_penalty"=> $creativity['frequency_penalty'],
            "presence_penalty"=> $creativity['presence_penalty'],
        ];
    }

    private function creativity($creativity){
        $creativity = [];

        if ($creativity == 'less') {
            $creativity = [  "temperature"  => 0.5, "frequency_penalty" => 0.08, "presence_penalty" => 0.11];
        }elseif ($creativity == 'more'){
            $creativity = [ "temperature"  => 1, "frequency_penalty" => 1, "presence_penalty" => 1.5];
        }elseif($creativity == 'outrageous'){
            $creativity = [ "temperature"  => 1, "frequency_penalty" => 1.5, "presence_penalty" => 1.9 ];
        }else{
            $creativity = [ "temperature"=> 1, "frequency_penalty"=> 0.16, "presence_penalty"=> 0.27];
        }
        return $creativity;
    }
}
