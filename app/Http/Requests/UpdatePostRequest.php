<?php


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdatePostRequest extends FormRequest
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
        
        // Update slug if title changed
        if ($this->post->title !== $validated['title']) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        // Set published_at when publishing for the first time
        if (isset($validated['published']) && $validated['published'] && !$this->post->published_at) {
            $validated['published_at'] = now();
        }

        return $validated;
    }
}