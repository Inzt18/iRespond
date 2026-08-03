<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered admin user (web only).
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                'unique:users,username',
            ],
            'password' => $this->passwordRules(),
            'mobile_number' => [
                'nullable',
                'regex:/^09\d{9}$/',
                Rule::unique('users', 'mobile_number'),
            ],
        ])->validate();

        $fullName = trim(implode(' ', array_filter([
            $input['first_name'],
            $input['middle_name'] ?? null,
            $input['last_name'],
        ])));

        return User::create([
            'first_name' => $input['first_name'],
            'middle_name' => $input['middle_name'] ?? null,
            'last_name' => $input['last_name'],
            'name' => $fullName,
            'email' => $input['email'],
            'username' => strtolower($input['username']),
            'password' => $input['password'],
            'mobile_number' => $input['mobile_number'] ?? null,
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
