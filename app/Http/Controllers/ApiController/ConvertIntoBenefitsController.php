<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\ConvertIntoBenefit;
use App\Models\ApiModel\EasyToRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConvertIntoBenefitsController extends Controller
{
//convert_into_benefits

    private $widget_code = 'convert_into_benefits';
    private $numberOfOutPuts = 10;

    public function convert_into_benefits(Request $request)
    {
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $convert_into_benefits = new ConvertIntoBenefit();
        $convert_into_benefits->request = serialize($request->toArray());
        $convert_into_benefits->user_id = 0;
        if ($user){
            $convert_into_benefits->user_id = $user->id;
        }
        $convert_into_benefits->topic = $request->topic;

        $convert_into_benefits->save();
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
                $convert_into_benefits->response = serialize($competition['response']);
                $convert_into_benefits->valid = 1;
                $convert_into_benefits->save();

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
