<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'nim' => 'required|string|max:50|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,user',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ];
    }
}