<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:10'],
            'published' => ['boolean'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();
        
        // Auto-generate slug from title
        $validated['slug'] = Str::slug($validated['title']);
        
        // Set published_at if publishing
        if (isset($validated['published']) && $validated['published']) {
            $validated['published_at'] = now();
        }

        return $validated;
    }
}