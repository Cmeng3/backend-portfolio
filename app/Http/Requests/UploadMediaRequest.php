<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'extensions:jpg,jpeg,png,webp,pdf'],
            'folder' => ['required', 'string', 'regex:~^portfolio/(profile|projects|blog|certificates|education|engineering|resume|branding)(/[a-z0-9]+(?:-[a-z0-9]+)*)?$~'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
