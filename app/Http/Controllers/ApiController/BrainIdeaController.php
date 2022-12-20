<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\ActionItem;
use App\Models\ApiModel\BrainIdea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BrainIdeaController extends Controller
{
    private $widget_code = 'brain_stormer';
    private $numberOfOutPuts = 3;

    public function brain_stormer(Request $request)
    {
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $brain_stormer = new BrainIdea();
        $brain_stormer->request = serialize($request->toArray());
        $brain_stormer->user_id = 0;
        if ($user){
            $brain_stormer->user_id = $user->id;
        }
        $brain_stormer->topic = $request->topic;

        $brain_stormer->save();
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
                if ($competition['code'] =! 200){
                    return response()->json($response);
                }
                // Complete  : Store Response
                $brain_stormer->response = serialize($competition['response']);
                $brain_stormer->valid = 1;
                $brain_stormer->save();

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
        $question = "brainstorm ideas on:\n\n" . $data['topic'] . "\n\n";
        $request = [
            "prompt"=> $question,
            "temperature"=> 1,
            "max_tokens"=> 200,
            "top_p"=> 1,
            "best_of"=> 3,
            "logprobs"=> 5,
            "n"=> $this->numberOfOutPuts,
            "frequency_penalty"=> 0.72,
            "presence_penalty"=> 0.86,
            "stop"=> ["5."]
        ];
        return $request;
    }
}
