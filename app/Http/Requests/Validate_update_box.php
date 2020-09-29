<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Validate_update_box extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return
        [
            'firstname' => 'required|min:3|max:20|alpha ',      // min:3 firstName:Aya
            'lastname' => 'required|min:3|max:20|alpha',
            'phone' => 'required|numeric|regex:/(01)[0-9]{9}/', // must be unique if other users use it
            'birthdate' => 'required|date',     //YYYY/MM/DD
        ];
    }
}
