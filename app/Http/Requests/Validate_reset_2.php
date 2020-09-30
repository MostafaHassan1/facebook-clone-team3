<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Validate_reset_2 extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return
        [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|confirmed' ,
            'token' => 'required'
        ];
    }
}
