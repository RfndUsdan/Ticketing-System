<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,normal,high',
            'category' => 'required|string|in:Fasilitas,Jaringan,Kebersihan,Administrasi,Lainnya',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ];
    }
}
