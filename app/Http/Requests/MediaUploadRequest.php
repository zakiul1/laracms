<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') || ($this->user()?->is_admin ?? false);
    }

    public function rules(): array
    {
        return [
            'files.*' => ['required', 'file', 'max:20480', 'mimetypes:image/*,video/mp4,video/webm,application/pdf'],
            // category targeting by term_taxonomy id (not required)
            'term_taxonomy_id' => ['nullable', 'integer'],
        ];
    }
}