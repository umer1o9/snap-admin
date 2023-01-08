<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\AllowedSearch;
use App\Models\GetSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GetSectionController extends Controller
{
    private $widget_code = 'get_section';
    public function __construct()
    {

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_section(Request $request)
    {
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $get_section = new GetSection();
        $get_section->request = serialize($request->toArray());
        $get_section->user_id = $user->id;
        $get_section->topic = $request->topic;
        $get_section->title = $request->title;
        $get_section->tone = $request->tone;
        $get_section->creativity = $request->creativity;
        $get_section->save();
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'tone' => ['required', 'string', 'max:255'],
            'creativity' => ['required', 'string', 'max:255'],
        ]);

        // TODO: check quota for consumed searches

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

        try {
            DB::beginTransaction();
            //Check Moderation
            $moderation_response = text_moderation($request->title);

            if ($moderation_response['code'] == 200){

                $request_data = $this->create_request($request->toArray());
               $request_data['user'] = (String)$user->id;
                $competition = competition_open_ai($request_data);
                if ($competition['code'] =! 200){
                    return response()->json($response);
                }
                // Complete  : Store Response
                $get_section->response = serialize($competition['response']);
                $get_section->valid = 1;
                $get_section->save();

                // TODO : Update Consumed history
                update_consumed_search_history($this->widget_code, $user->id, $allowed_searches);

                // TODO : Return Success
                $response = ['code' => 200, 'status' => true, 'message' => 'Success', 'data' =>  json_decode($competition['response'])->choices, 'request' => $request_data ];
                DB::commit();
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
       $creativity = $this->creativity($data['creativity']);
       $question = "Write a 1500 words long blog post " . $data['tone'] . ".\n\nBlog Title:" . $data['title'] . "\nKeywords:" . $data['topic'] . "\n\n";
        $request = [
            "prompt"=> $question,
          "temperature"=> $creativity['temperature'],
          "max_tokens"=> 950,
          "top_p"=> 1,
          "logprobs"=> 5,
          "frequency_penalty"=> $creativity['frequency_penalty'],
          "presence_penalty"=> $creativity['presence_penalty'],
//          "user"=> this.myauth.userid$,
        ];
        return $request;
    }

    private function creativity($creativity){
        $creativity = [];

        if ($creativity == 'less') {
            $creativity = [  "temperature"  => 0.3, "frequency_penalty" => 0.1, "presence_penalty" => 0.1];
        }elseif ($creativity == 'more'){
            $creativity = [ "temperature"  => 0.8, "frequency_penalty" => 0.8, "presence_penalty" => 0.7];
        }elseif($creativity == 'outrageous'){
            $creativity = [ "temperature"  => 1, "frequency_penalty" => 1.7, "presence_penalty" => 1.4 ];
        }else{
            $creativity = [ "temperature"=> 0.5, "frequency_penalty"=> 0.25, "presence_penalty"=> 0.21];
        }
        return $creativity;
    }

}
