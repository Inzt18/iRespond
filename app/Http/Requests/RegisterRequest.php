<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mobile_number' => [
                'required',
                'digits:11',
                'regex:/^09[0-9]{9}$/',
                'unique:users,mobile_number',
            ],
            'otp' => [
                'required',
                'digits:6',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile_number.required' => 'Mobile number is required.',
            'mobile_number.digits' => 'Mobile number must be exactly 11 digits.',
            'mobile_number.regex' => 'Mobile number must start with 09.',
            'mobile_number.unique' => 'This mobile number is already registered.',
            'otp.required' => 'OTP code is required.',
            'otp.digits' => 'OTP code must be exactly 6 digits.',
        ];
    }
}
