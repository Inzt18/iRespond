<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * @param array<string, string> $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'mobile_number' => [
                'required',
                'regex:/^09\d{9}$/',
                'unique:users,mobile_number',
            ],
        ])->validate();

        return User::create([
            'mobile_number' => $input['mobile_number'],
        ]);
    }
}