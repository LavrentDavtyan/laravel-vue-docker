<?php

namespace App\Http\Requests\Share;

use Illuminate\Foundation\Http\FormRequest;

class StoreShareTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title'    => ['required', 'string', 'max:120'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }
}
