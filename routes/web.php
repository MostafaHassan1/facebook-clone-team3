<?php

use Illuminate\Support\Facades\Route;

use App\Mail\MailtrapExample;
use Illuminate\Support\Facades\Mail;


Route::get('user/verify/{verification_code}', 'AuthController@verifyUser');

Route::get('/', function () {
    return view('welcome');
});
