<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; 


class TicketReplyStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return [
            'content' => 'required|string',
            'status' => $user && $user->role == 'admin' ? 'required|in:open,in_progress,resolved,rejected' : 'nullable',
        ];
    }
}