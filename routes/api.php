<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router)
{
    Route::Post('edit_profile', 'AuthController@edit_profile');
    Route::Post('logout', 'AuthController@logout');
    Route::Post('signin', 'AuthController@signin');
    Route::post('login', 'AuthController@login')->name('login');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
    Route::post('change_password', 'AuthController@change_password');

    Route::Post('reset_password', 'AuthController@reset_password');
    Route::post('reset_pass/{token}','AuthController@reset_password_2');

});
