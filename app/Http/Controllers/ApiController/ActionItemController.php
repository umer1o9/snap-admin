<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\ActionItem;
use App\Models\ApiModel\GetTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ActionItemController extends Controller
{
    private $widget_code = 'action_item';
    private $numberOfOutPuts = 10;

    public function action_item(Request $request)
    {
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $action_item = new ActionItem();
        $action_item->request = serialize($request->toArray());
        $action_item->user_id = 0;
        if ($user){
            $action_item->user_id = $user->id;
        }
        $action_item->topic = $request->topic;

        $action_item->save();
        $validated = $request->validate([
            'topic' => ['required', 'string'],
        ]);

        // TODO: check quota for consumed searches

        if ($user){
            $allowed_searches =  check_consumed_searches($this->widget_code, $user->id);
            $allowed_searches = json_decode($allowed_searches);
            if ($allowed_searches->status == false){
                $response['message'] = 'Search limit reached.';
                $response['description'] = 'You have hit your search limit and exhausted free searches quota also. To continue, Buy your search quota';
                return response()->json($response);
            }
        }

        try {
        DB::beginTransaction();
        //Check Moderation

        $moderation_response = text_moderation($request->topic);

        if ($moderation_response['code'] == 200){
            $request_data = $this->create_request($request->toArray());
            $request_data['user'] = '0';
            if ($user){
                $request_data['user'] = (String)$user->id;
            }
            $competition = competition_open_ai($request_data);
            if ($competition['code'] == 200){
                $text = json_decode($competition['response'])->choices[0]->text;
                $request_data = $this->create_second_request($text);
                $competition = competition_open_ai($request_data);
            }else{
                return response()->json($response);
            }
            if ($competition['code'] =! 200){
                return response()->json($response);
            }
            // Complete  : Store Response
            $action_item->response = serialize($competition['response']);
            $action_item->valid = 1;
            $action_item->save();

            // TODO : Update Consumed history
            if ($user){
                update_consumed_search_history($this->widget_code, $user->id, $allowed_searches);
            }

            // TODO : Return Success
            $response = ['code' => 200, 'status' => true, 'message' => 'Success', 'data' =>  json_decode($competition['response'])->choices, 'request' => $request_data ];
            DB::commit();
            return response()->json($response);
        }
        }
        catch (\Exception $ex) {
            return response()->json(['code' => 422 , 'status' => false ,'message' => $ex->getMessage()]);
        }
    }

    public function create_request($data){
        $question = "Make it easy to read by breaking into short paragraphs:\n\n" . $data['topic'] . "\n\n";
        $request = [
            "prompt"=> $question,
            "temperature"=> 0,
            "max_tokens"=> 800,
            "top_p"=> 1,
            "logprobs"=> 5,
            "frequency_penalty"=> 0,
            "presence_penalty"=> 0,

        ];
        return $request;
    }
    public function create_second_request($data){
        $question = "Classify the Observations and Action Items from this block of text:\n\n" . $data . " \n\n";
        return [
            "prompt"=> $question,
            "temperature"=> 0,
            "suffix"=> "Action Items:",
            "max_tokens"=> 400,
            "top_p"=> 1,
            "logprobs"=> 5,
            "frequency_penalty"=> 0,
            "presence_penalty"=> 0,
        ];
    }
}
