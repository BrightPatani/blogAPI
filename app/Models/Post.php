<?php 

// app/Models/Post.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'image',        
        'video',        
        'media_type',   
        'published',
        'published_at',
    ];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'image_url',
        'video_url',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
        
        // Clean up media files when post is deleted
        static::deleting(function ($post) {
            if ($post->image && Storage::disk('public')->exists($post->image)) {
                Storage::disk('public')->delete($post->image);
            }
            if ($post->video && Storage::disk('public')->exists($post->video)) {
                Storage::disk('public')->delete($post->video);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function scopePublished($query)
    {
        return $query->where('published', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }
    
    public function isPublished(): bool
    {
        return (bool) ($this->published && $this->published_at && $this->published_at <= now());
    }
    
    // Helper method to get full image URL
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
    
    // Helper method to get full video URL
    public function getVideoUrlAttribute()
    {
        return $this->video ? asset('storage/' . $this->video) : null;
    }
}