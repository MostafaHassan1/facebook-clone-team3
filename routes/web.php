<?php

use Illuminate\Support\Facades\Route;


Route::get('user/verify/{verification_code}', 'AuthController@verifyUser');
Route::get('reset_pass/{vcode}/{password}','AuthController@update_password');


Route::get('/', function () {
    return view('welcome');
});
