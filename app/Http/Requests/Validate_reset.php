<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Validate_reset extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return
        [
            'email' => 'required|exists:users|email:rfc,dns',
        ];
    }
}
