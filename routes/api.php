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
    /*1*/Route::Post('signin','AuthController@signin');
    /*2*/Route::Post('verify/{verification_code}','AuthController@verifyUser');
    /*3*/Route::post('login', 'AuthController@login');
    /*4*/Route::Post('edit_profile', 'AuthController@edit_profile');
    /*5*/Route::post('change_password', 'AuthController@change_password');
    /*6*/Route::Post('sendresetpasswordemail', 'AuthController@sendresetpasswordemail');
    /*7*/Route::post('reset_pass/{token}','AuthController@reset_password_2');
    /*8*/Route::post('refresh', 'AuthController@refresh');
    /*9*/Route::post('me', 'AuthController@me');
    /*10*/Route::Post('logout','AuthController@logout');
    });
