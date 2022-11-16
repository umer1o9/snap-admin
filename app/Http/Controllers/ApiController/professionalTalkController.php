<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\LinkedinPost;
use App\Models\ApiModel\ProfessionalTalk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class professionalTalkController extends Controller
{
    private $widget_code = 'professional_talk';

    //
    public function index(Request $request){
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $professional_talk = new ProfessionalTalk();
        $professional_talk->request = serialize($request->toArray());
        $professional_talk->user_id = $user->id;
        $professional_talk->message = $request->message;

        $professional_talk->save();
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:255'],
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
            $moderation_response = text_moderation($request->message);
            if ($moderation_response['code'] == 200) {
                $request_data = $this->create_request($request->toArray());
                $request_data['user'] = (String)$user->id;
                $competition = competition_open_ai($request_data);
                if ($competition['code'] =! 200){
                    return response()->json($response);
                }
                // Complete  : Store Response
                $professional_talk->response = serialize($competition['response']);
                $professional_talk->valid = 1;
                $professional_talk->save();

                // TODO : Update Consumed history
                update_consumed_search_history($this->widget_code, $user->id, $allowed_searches);

                // TODO : Return Success
                $response = ['request_data' => $request_data, 'code' => 200, 'status' => true, 'message' => 'Success', 'id' => $professional_talk->id, 'data' =>  json_decode($competition['response'])->choices ];
                DB::commit();
                return response()->json($response);
            }else{
                $response['message'] = 'Moderation Error';
                $response['description'] = 'Please Select valid Keywords.';
                return response()->json($response);
            }
        }
        catch (\Exception $ex) {
            return response()->json(['code' => 422 , 'status' => false ,'message' => $ex->getMessage()]);
        }
    }

    public function create_request($data){
        $question = "Make it easy to read by breaking into short paragaphs:\n\n" . $data['message'] . "\n\n";

        return [
            "prompt"=> $question,
            "temperature"=> 0,
            "max_tokens"=> 900,
            "top_p"=> 1,
            "n"=> 1,
            "best_of"=> 2,
            "logprobs"=> 5,
            "frequency_penalty"=> 0,
            "presence_penalty"=> 0,
        ];
    }
}
