<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Validate_signup extends FormRequest
{
    public function authorize()
    {
        return true;

    }

    public function rules()
    {
        return [
                'firstname' => 'required|min:3|max:20|alpha ',      // min:3 firstName:Aya
                'lastname' => 'required|min:3|max:20|alpha',
                'email' => 'required|email:rfc,dns|unique:users',
                'password' => 'required|min:8|max:50',   //'password' => 'password:api' // password accept Spaces
                'gender' => 'required|between:1,2|integer', // 1 = Man , 2 = Woman
                'birthdate' => 'required|date',     //YYYY/MM/DD
                //'phone' => 'required|numeric|unique:users|regex:/(01)[0-9]{9}/',


        ];
    }
}
