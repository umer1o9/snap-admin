<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\SalesCopy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesCopyController extends Controller
{
    private $widget_code = 'sales_copy';
    private $number_of_choices = 3;

    //
    public function sales_copy(Request $request){
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();
        $sales_copy = new SalesCopy();
        $sales_copy->request = serialize($request->toArray());
        $sales_copy->user_id = $user->id;
        $sales_copy->about = $request->about;
        $sales_copy->writing_style = $request->writing_style;
        $sales_copy->creativity = $request->creativity;
        $sales_copy->save();
        $validated = $request->validate([
            'about' => ['required', 'string'],
            'writing_style' => ['required', 'string', 'max:255'],
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
            $moderation_response = text_moderation($request->about);
            if ($moderation_response['code'] == 200) {
                $request_data = $this->create_request($request->toArray());
                $request_data['user'] = (String)$user->id;
                $competition = competition_open_ai($request_data);
                if ($competition['code'] =! 200){
                    return response()->json($response);
                }
                // Complete  : Store Response
                $sales_copy->response = serialize($competition['response']);
                $sales_copy->valid = 1;
                $sales_copy->save();

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
            return response()->json(['code' => 422 , 'status' => false ,'message' => $ex->getMessage()]);
        }
    }

    public function create_request($data){
        $creativity = $this->creativity($data['creativity']);
        if ($data['writing_style'] == 'storymode'){ $question = "Write a sales copy in storytelling mode to sell the following:\n\n" . $data['about'] . "\n\n";}
        else{$question = "Write a sales copy in a " . $data['writing_style'] . " style to sell the following:\n\n" . $data['about'] . "\n\n";}

        return [
            "prompt"=> $question,
            "temperature"=> $creativity['temperature'],
            "max_tokens"=> 450,
            "top_p"=> 1,
            "n" => $this->number_of_choices,
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
