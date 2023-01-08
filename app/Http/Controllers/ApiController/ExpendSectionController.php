<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\GetSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpendSectionController extends Controller
{
    //
    private $widget_code = 'expend_blogpost';

    public function expend_section(Request $request)
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
                $response = ['code' => 200, 'status' => true, 'message' => 'Success', 'data' =>  json_decode($competition['response'])->choices ];
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
}
