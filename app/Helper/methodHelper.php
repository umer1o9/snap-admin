<?php

use App\Models\ApiModel\AllowedSearch;
use App\Models\ApiModel\ConsumedSearchHistory;
use App\Models\ApiModel\Sales;
use App\Models\ApiModel\Widget;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;

if (! function_exists('get_current_user')){
    function get_current_user(){
        $user = Auth::user();
        return $user;
//        return ['name' => 'umer', 'Age' => '25'];
    }
}


if (! function_exists('text_moderation')){

    function text_moderation($input){
        $response = ['status' => true, 'code' => 200, 'message' => 'Cleared!','data' => []];
        $client = new \GuzzleHttp\Client();
        $url =  config('constants.open_api_base_url')."/moderations";
        $headers = [
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.env('API_TOKEN' ),
        ];

        $moderation_response = $client->request('POST', $url, [
            'verify' => false,
            'headers' => $headers,
            'body' => json_encode(['input' => $input ])
        ]);

        $status = $moderation_response->getStatusCode();

        $message = 'Cleared!';
        $resp_status = true;
        $moderation_response = json_decode($moderation_response->getBody());
        $categories = $moderation_response->results[0]->categories;
        if ($status == 200){
            foreach ($categories as $key => $category){
                if ($categories->$key == true){
                    $resp_status = false;
                    $message = 'please Provide a Correct Content. '. $key . ' detected' ;
                    $response = ['status' => $resp_status, 'code' => 402, 'message' => $message,'data' => ['input' => $input]];
                }
            }
        }

        return $response;
    }


    if (! function_exists('competition_open_ai')){
        /**
         * @throws GuzzleException
         */
        function competition_open_ai($data): array
        {
            $client = new \GuzzleHttp\Client();
            $url =  config('constants.open_api_base_url')."/engines/text-davinci-002/completions";
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.env('API_TOKEN' ),
            ];

//            $data['temperature'] = null;
            $open_ai_response = $client->request('POST', $url, [
                'verify' => false,
                'headers' => $headers,
                'body' => json_encode($data)
            ]);

            $status = $open_ai_response->getStatusCode();
            $response = $open_ai_response->getBody()->getContents();

            return ['code' => $status, 'response' => $response];
        }
    }

    if (! function_exists('check_consumed_searches')) {
        function check_consumed_searches($widget_code, $user_id){
            //TODO: get both type of Sales
            $sales = Sales::with(['plans'=> function($q){
                $q->where('status', 1);
            }, 'allowed_searches'])->where('user_id', $user_id)->get();

            //TODO: filter plan types
            $no_of_searches = 0;
            $no_of_all_searches = 0;
            $custom_plan_ids = [];
            $all_plan_ids = [];
            foreach ($sales as $sale){
                if ($sale->plans){
                    if ($sale->plans->name == 'all_widgets'){
                        $no_of_all_searches += $sale->allowed_searches->id;
                        $all_plan_ids[] = $sale->id;
                    }else{
                        $no_of_searches += $sale->plans->no_of_allowed_searches;
                        $custom_plan_ids[] = $sale->allowed_searches->id;
                    }
                }
            }


            $allowed_searches = AllowedSearch::where('user_id', $user_id)->whereIn('sale_id', $custom_plan_ids)->where('is_closed', 0)->get();
            $allowed_searches_all = AllowedSearch::where('user_id', $user_id)->whereIn('sale_id', $all_plan_ids)->where('is_closed', 0)->get();
            $widget = Widget::where('code', $widget_code)->first();
            if ($allowed_searches != null){

                foreach ($allowed_searches as $allowed_search) {
                    //Calculate consumed Searches
                    $consumed_searches = ConsumedSearchHistory::where('widget_id', $widget->id)->where('allowed_search_id', $allowed_search->id)->where('user_id', $user_id)->get();
                    if (count($consumed_searches) < $allowed_search->$widget_code || $allowed_search->$widget_code == null){
                        return json_encode(['status' => true, 'allowed_search_id' => $allowed_search->id, 'pending' => $allowed_search->$widget_code - count($consumed_searches)]);
                    }
//                    else{
//                        $allowed_search->is_closed = 1;
//                        $allowed_search->save();
//                    }
                }
            }

            if ($allowed_searches_all != null){
                foreach ($allowed_searches_all as $allowed_search) {
                    //Calculate consumed Searches
                    $consumed_searches = ConsumedSearchHistory::where('widget_id', $widget->id)->where('allowed_search_id', $allowed_search->id)->where('user_id', $user_id)->get();
                    if (count($consumed_searches) < $allowed_search->$widget_code || $allowed_search->$widget_code == null){
                        return json_encode(['status' => true, 'allowed_search_id' => $allowed_search->id, 'pending' => $allowed_search->$widget_code - count($consumed_searches)]);
                    }
                }
            }
            return json_encode(['status' => false, 'allowed_search_id' => null, 'pending' => null]);
        }
    }

    if (! function_exists('update_consumed_search_history')) {
        function update_consumed_search_history($widget_code, $user_id, $allowed_searches){
            $widget = Widget::where('code', $widget_code)->first();
            $consumed_searches = new ConsumedSearchHistory();
            $consumed_searches->user_id = $user_id;
            $consumed_searches->widget_id = $widget->id;
            $consumed_searches->allowed_search_id = $allowed_searches->allowed_search_id;
            $consumed_searches->pending_searches = $allowed_searches->pending;
            $consumed_searches->widget_code = $widget_code;
            $consumed_searches->save();
        }
    }

    if (! function_exists('update_consume_search_status')) {
        function update_consume_search_status($allowed_searches){
            $allowed_search = AllowedSearch::find($allowed_searches->allowed_search_id);
            $allowed_search->is_closed = 1;
            $allowed_search->save();
        }
    }
}
