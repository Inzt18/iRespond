<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'mobile_number' => [
                'required',
                'digits:11',
                'regex:/^09[0-9]{9}$/',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.digits' => 'Mobile number must be exactly 11 digits.',
            'mobile_number.regex' => 'Mobile number must start with 09.',
        ];
    }
}