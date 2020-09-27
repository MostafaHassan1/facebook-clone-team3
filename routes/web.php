<?php

use Illuminate\Support\Facades\Route;


Route::get('user/verify/{verification_code}', 'AuthController@verifyUser');

Route::get('/', function () {
    return view('welcome');
});
