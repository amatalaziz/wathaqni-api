<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $registering = $this->routeIs('api.v1.auth.register');

        $rules = [
            'phone' => ['bail', 'required', 'string', 'regex:/^\+[1-9][0-9]{1,14}$/D'],
            'password' => [
                'bail', 'required', 'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strlen($value) > 72) {
                        $fail('The password must not exceed 72 bytes.');
                    }
                },
            ],
        ];

        if ($registering) {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['phone'][] = 'unique:users,phone';
            $rules['password'][] = 'confirmed';
            $rules['password'][] = Password::min(12);
        }

        return $rules;
    }
}
