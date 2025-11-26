<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteVideo extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'note_id',
        'video_url',
        'thumbnail_url',
        'platform',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-detect platform when creating or updating if not manually set
        static::creating(function (NoteVideo $video) {
            if (empty($video->platform) && !empty($video->video_url)) {
                $video->platform = self::detectPlatform($video->video_url);
            }
        });

        static::updating(function (NoteVideo $video) {
            // Re-detect platform if video_url changed and platform not manually set
            if ($video->isDirty('video_url') && !$video->isDirty('platform')) {
                $video->platform = self::detectPlatform($video->video_url);
            }
        });
    }

    /**
     * Get the note that owns the video.
     */
    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    /**
     * Detect the video platform from the URL.
     *
     * @param string $url
     * @return string|null
     */
    public static function detectPlatform(string $url): ?string
    {
        $url = strtolower($url);

        // YouTube patterns
        $youtubePatterns = [
            'youtube.com',
            'youtu.be',
            'youtube-nocookie.com',
        ];

        foreach ($youtubePatterns as $pattern) {
            if (str_contains($url, $pattern)) {
                return 'youtube';
            }
        }

        // Dailymotion patterns
        $dailymotionPatterns = [
            'dailymotion.com',
            'dai.ly',
        ];

        foreach ($dailymotionPatterns as $pattern) {
            if (str_contains($url, $pattern)) {
                return 'dailymotion';
            }
        }

        // Return null if platform cannot be detected
        // User can manually set to 'other' if needed
        return null;
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
