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
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // Max 5MB
            'video' => ['nullable', 'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo', 'max:51200'], // Max 50MB
            'categories' => ['nullable', 'array'],
            'categories.*' => ['exists:categories,id'],
            'published' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, jpg, png, gif, webp.',
            'image.max' => 'The image must not be larger than 5MB.',
            'video.mimetypes' => 'The video must be a file of type: mp4, mpeg, mov, avi.',
            'video.max' => 'The video must not be larger than 50MB.',
            'categories.*.exists' => 'One or more selected categories do not exist.',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();
        
        // Auto-generate slug from title
        $validated['slug'] = Str::slug($validated['title']);
        
        // Set default media type
        if (!isset($validated['media_type'])) {
            $validated['media_type'] = 'none';
        }
        
        // Set published_at if publishing
        if (isset($validated['published']) && $validated['published']) {
            $validated['published_at'] = now();
        }

        return $validated;
    }
}