<?php

    use App\Http\Controllers\Auth\AuthController;
    use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test_emails', function () {
    signup_email_test();
});
Route::get('/buy_plan_email_test', function () {
    buy_plan_email_test();
});


    Route::get('login', [AuthController::class, 'index'])->name('login');

    Route::post('login', [AuthController::class, 'postLogin'])->name('login.post');

    Route::get('registration', [AuthController::class, 'registration'])->name('register');

//    Route::post('post-registration', [AuthController::class, 'postRegistration'])->name('register.post');

    Route::get('dashboard', [AuthController::class, 'dashboard']);

    Route::get('logout', [AuthController::class, 'logout'])->name('logout');

    Route::group(['prefix'=>'admin','as'=>'admin.', 'namespace' => 'WebController'], function() {

        Route::group(['prefix'=>'widgets','as'=>'widgets.'], function(){
            Route::get('/', ['as' => 'index', 'uses' => 'WidgetController@index']);
            Route::get('/create', ['as' => 'create', 'uses' => 'WidgetController@create']);
        });

    });

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
