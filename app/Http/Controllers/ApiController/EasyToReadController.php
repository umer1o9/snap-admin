<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\ActionItem;
use App\Models\ApiModel\EasyToRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EasyToReadController extends Controller
{
    private $widget_code = 'easy_to_read';
    private $numberOfOutPuts = 10;

    public function easy_to_read(Request $request)
    {
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $easy_to_read = new EasyToRead();
        $easy_to_read->request = serialize($request->toArray());
        $easy_to_read->user_id = 0;
        if ($user){
            $easy_to_read->user_id = $user->id;
        }
        $easy_to_read->topic = $request->topic;

        $easy_to_read->save();
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
                $easy_to_read->response = serialize($competition['response']);
                $easy_to_read->valid = 1;
                $easy_to_read->save();

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
        $question = "Summarize this for a second-grade student:\n\n" . $data['topic'] . "\n\n";
        $request = [
            "prompt"=> $question,
            "temperature"=> 0.7,
            "max_tokens"=> 400,
            "best_of"=> 4,
            "top_p"=> 1,
            "logprobs"=> 5,
            "frequency_penalty"=> 0,
            "presence_penalty"=> 0,
        ];
        return $request;
    }
}
