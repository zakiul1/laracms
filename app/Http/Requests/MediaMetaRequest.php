<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaMetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') || ($this->user()?->is_admin ?? false);
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            // move/attach category (term_taxonomy id)
            'term_taxonomy_id' => ['nullable', 'integer'],
        ];
    }
}