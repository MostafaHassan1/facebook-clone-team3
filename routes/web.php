<?php

use Illuminate\Support\Facades\Route;

use App\Mail\MailtrapExample;
use Illuminate\Support\Facades\Mail;

Route::get('/send-mail', function () {

    Mail::to('newuser@example.com')->send(new MailtrapExample());

    return 'A message has been sent to Mailtrap!';

});


Route::get('/', function () {
    return view('welcome');
});
// test only // sry new test //
