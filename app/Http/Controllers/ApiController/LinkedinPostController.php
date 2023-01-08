<?php

namespace App\Http\Controllers\ApiController;

use App\Http\Controllers\Controller;
use App\Models\ApiModel\CoWrite;
use App\Models\ApiModel\LinkedinPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LinkedinPostController extends Controller
{
    private $widget_code = 'linkedin_post';


    //
    public function index(Request $request){
        $response = ['code' => 422 , 'status' => false ,'message' => 'Server Error', 'description' => 'Some error please contact Admin or check your input.', 'data' => []];
        $user = Auth::user();

        $linkedin_post = new LinkedinPost();
        $linkedin_post->request = serialize($request->toArray());
        $linkedin_post->user_id = $user->id;
        $linkedin_post->topic = $request->topic;

        $linkedin_post->save();
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
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
            $moderation_response = text_moderation($request->topic);
            if ($moderation_response['code'] == 200) {
                $request_data = $this->create_request($request->toArray());
                $request_data['user'] = (String)$user->id;
                $competition = competition_open_ai($request_data);
                if ($competition['code'] =! 200){
                    return response()->json($response);
                }
                // Complete  : Store Response
                $linkedin_post->response = serialize($competition['response']);
                $linkedin_post->valid = 1;
                $linkedin_post->save();

                // TODO : Update Consumed history
                update_consumed_search_history($this->widget_code, $user->id, $allowed_searches);

                // TODO : Return Success
                $response = ['request_data' => $request_data, 'code' => 200, 'status' => true, 'message' => 'Success', 'id' => $linkedin_post->id, 'data' =>  json_decode($competition['response'])->choices ];
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
        $question = "Topic: Automated content writing is the future. Post: Content writing automation with AI is leading the charge! With AI-based tools, you'll be able to create content that is both high quality and on-brand, without having to lift a finger.\nAs a business owner, you know that content is key. But creating high-quality content can be time-consuming – especially if you’re writing multiple pieces a day.\nFortunately, there’s a solution: automated content writing. Automated content writing is the future of content marketing, and it’s already helped businesses of all sizes create more content in less time.\nHere’s how it works: you provide the tool with a topic and keywords, and the tool will generate a high-quality article based on that topic. You can then publish the article on your website or blog, or share it on social media.\nAutomated content writing tools are getting better and better, and they’re becoming more and more accurate. So if you’re looking to create more content in less time, automated content writing is the way to go.\n#contentwriting #futureofcontent #automatedcontent #content#writing\n\nTopic:" . $data['topic'] ."\nPost:";

        return [
            "prompt"=> $question,
            "temperature"=> 0.5,
            "max_tokens"=> 450,
            "top_p"=> 1,
            "n"=> 1,
            "best_of"=> 2,
            "logprobs"=> 5,
            "frequency_penalty"=> 0.16,
            "presence_penalty"=> 0,
        ];
    }

}
