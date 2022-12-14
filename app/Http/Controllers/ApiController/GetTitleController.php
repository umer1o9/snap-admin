<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\GetTitle;
use App\Models\GetSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GetTitleController extends Controller
{
    //
    private $widget_code = 'get_title';
    private $numberOfOutPuts = 10;

    public function get_title(Request $request)
    {
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $get_title = new GetTitle();
        $get_title->request = serialize($request->toArray());
        $get_title->user_id = 0;
        if ($user){
            $get_title->user_id = $user->id;
        }
        $get_title->topic = $request->topic;

        $get_title->save();
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
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

//        try {
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
                $get_title->response = serialize($competition['response']);
                $get_title->valid = 1;
                $get_title->save();

                // TODO : Update Consumed history
                if ($user){
                    update_consumed_search_history($this->widget_code, $user->id, $allowed_searches);
                }

                // TODO : Return Success
                $response = ['code' => 200, 'status' => true, 'message' => 'Success', 'data' =>  json_decode($competition['response'])->choices ];
                DB::commit();
                return response()->json($response);
            }
//        }
//        catch (\Exception $ex) {
//            return response()->json(['code' => 422 , 'status' => false ,'message' => $ex->getMessage()]);
//        }
    }

    public function create_request($data){
        $question = "Suggest a witty article title for '" . $data['topic'] . "':\n\n";
        $request = [
            "prompt"=> $question,
            "temperature"=> 1,
            "max_tokens"=> 250,
            "n"=> $this->numberOfOutPuts,
            "best_of" =>  $this->numberOfOutPuts,
            "top_p"=> 1,
            "logprobs"=> 5,
            "frequency_penalty"=> 0.37,
            "presence_penalty"=> 0,
        ];
        return $request;
    }
}
