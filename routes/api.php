<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


//Login User
Route::post('/user/login', 'ApiController\CustomerController@customer_login');
//Register User
Route::post('/user/register', 'ApiController\CustomerController@register');
//Constact US
Route::post('/contact_us', ['as' => '/contact_us', 'uses' => 'ApiController\CustomerController@contact_us']);


Route::group(['prefix'=>'user','as'=>'workflow', 'middleware' => ['auth:sanctum'], 'namespace' => 'ApiController' ], function(){
    Route::get('/', ['as' => '/', 'uses' => 'CustomerController@get_user_detail']);
    Route::post('/', ['as' => '', 'uses' => 'CustomerController@update_profile']);
    Route::post('/update_password', ['as' => '/update_password', 'uses' => 'CustomerController@update_password']);
});

Route::group(['prefix'=>'workflow','as'=>'workflow', 'middleware' => ['auth:sanctum'], 'namespace' => 'ApiController' ], function(){
    Route::post('/get_section', ['as' => 'get_section', 'uses' => 'GetSectionController@get_section']);
    Route::post('/get_title', ['as' => 'get_title', 'uses' => 'GetTitleController@get_title']);
});

    //Get Title open API
    Route::post('/get_title', ['as' => 'get_title', 'uses' => 'ApiController\GetTitleController@get_title']);

    Route::get('widget/get_categories', ['as' => '/get_categories', 'uses' => 'ApiController\WidgetController@get_categories']);
    Route::get('widget/', ['as' => '/', 'uses' => 'ApiController\WidgetController@index']);

    Route::post('compilation/', ['as' => '/', 'uses' => 'ApiController\WidgetController@compilation']);

    Route::get('widget/result', ['as' => 'widget/result', 'uses' => 'ApiController\WidgetController@result']);
Route::group(['prefix'=>'widget', 'middleware' => ['auth:sanctum'], 'as'=>'widget', 'namespace' => 'ApiController' ], function(){
    Route::get('/results', ['as' => '/results', 'uses' => 'WidgetController@results']);

    Route::post('/sales_copy', ['as' => 'sales_copy', 'uses' => 'SalesCopyController@sales_copy']);
    Route::post('/co_write', ['as' => '/co_write', 'uses' => 'CoWriteController@co_writing']);
    Route::post('/linkedin_post', ['as' => '/linkedin_post', 'uses' => 'LinkedinPostController@index']);
    Route::post('/professional_talk', ['as' => '/professional_talk', 'uses' => 'professionalTalkController@index']);
    Route::post('/video_script', ['as' => '/video_script ', 'uses' => 'VideoScriptController@index']);
//NEW
    Route::post('/action_item', ['as' => '/action_item ', 'uses' => 'ActionItemController@action_item']);
    Route::post('/easy_to_read', ['as' => '/easy_to_read ', 'uses' => 'EasyToReadController@easy_to_read']);
    Route::post('/convert_into_benefits', ['as' => '/convert_into_benefits ', 'uses' => 'ConvertIntoBenefitsController@convert_into_benefits']);
    Route::post('/brain_stormer', ['as' => '/brain_stormer ', 'uses' => 'BrainIdeaController@brain_stormer']);

   //Voting
    Route::post('/vote', ['as' => '/vote', 'uses' => 'WidgetController@vote_for_widget']);
});
//Payment Gateway
    Route::get('/payment_gateways', 'ApiController\PaymentMethodController@index');
    Route::group(['prefix'=>'', 'middleware' => ['auth:sanctum'], 'as'=>'widget', 'namespace' => 'ApiController' ], function(){
        Route::post('/register_plan', 'PaymentMethodController@register_plan');
        Route::post('/user_sales', 'PaymentMethodController@sales');
    });

Route::post('/tokens/create', function (Request $request) {
	//dd($request->toArray());
    $token = $request->user()->createToken($request->token_name);

    return ['token' => $token->plainTextToken];
});


