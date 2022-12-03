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

Route::group(['prefix'=>'user','as'=>'workflow', 'middleware' => ['auth:sanctum'], 'namespace' => 'ApiController' ], function(){
    Route::get('/', ['as' => '/', 'uses' => 'CustomerController@get_user_detail']);
    Route::post('/', ['as' => '', 'uses' => 'CustomerController@update_profile']);
    Route::post('/update_password', ['as' => '/update_password', 'uses' => 'CustomerController@update_password']);
});

Route::group(['prefix'=>'workflow','as'=>'workflow', 'middleware' => ['auth:sanctum'], 'namespace' => 'ApiController' ], function(){
    Route::post('/get_section', ['as' => 'get_section', 'uses' => 'GetSectionController@get_section']);
    Route::post('/get_title', ['as' => 'get_title', 'uses' => 'GetTitleController@get_title']);
});

Route::group(['prefix'=>'widget','as'=>'widget', 'namespace' => 'ApiController' ], function(){
    Route::get('/', ['as' => '/', 'uses' => 'WidgetController@index']);
    Route::get('/results', ['as' => '/results', 'uses' => 'WidgetController@results']);
    Route::get('/get_categories', ['as' => '/get_categories', 'uses' => 'WidgetController@get_categories']);

    Route::post('/sales_copy', ['as' => 'sales_copy', 'uses' => 'SalesCopyController@sales_copy']);
    Route::post('/co_write', ['as' => '/co_write', 'uses' => 'CoWriteController@co_writing']);
    Route::post('/linkedin_post', ['as' => '/linkedin_post', 'uses' => 'LinkedinPostController@index']);
    Route::post('/professional_talk', ['as' => '/professional_talk', 'uses' => 'professionalTalkController@index']);
    Route::post('/video_script ', ['as' => '/video_script ', 'uses' => 'VideoScriptController@index']);

});
//Route::get('widget', ['as' => 'widget', 'uses' => 'WidgetController@index']);
//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    Route::post('/workflow/get_section', ['as' => 'get_section', 'uses' => 'GetSectionController@index']);
//
//});


Route::post('/tokens/create', function (Request $request) {
	//dd($request->toArray());
    $token = $request->user()->createToken($request->token_name);

    return ['token' => $token->plainTextToken];
});


