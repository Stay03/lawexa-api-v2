<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'original_name',
        'filename',
        'path',
        'disk',
        'mime_type',
        'size',
        'category',
        'url',
        'metadata',
        'fileable_type',
        'fileable_id',
        'uploaded_by',
        'upload_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'path', // Hide the actual storage path for security
    ];

    /**
     * Get the parent fileable model (Court Case, User, etc.).
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the file's download URL.
     */
    public function getDownloadUrlAttribute(): string
    {
        if ($this->disk === 's3') {
            return Storage::disk('s3')->url($this->path);
        }
        
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get the file size in human readable format.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the file is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the file is a document.
     */
    public function getIsDocumentAttribute(): bool
    {
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'application/rtf',
            'text/rtf'
        ];
        
        return in_array($this->mime_type, $documentMimes);
    }

    /**
     * Get the file extension from the original filename.
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    /**
     * Check if upload is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->upload_status === 'pending';
    }

    /**
     * Check if upload is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->upload_status === 'completed';
    }

    /**
     * Check if upload failed.
     */
    public function getIsFailedAttribute(): bool
    {
        return $this->upload_status === 'failed';
    }

    /**
     * Scope to filter files by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter files by type.
     */
    public function scopeByType($query, string $type)
    {
        if ($type === 'image') {
            return $query->where('mime_type', 'like', 'image/%');
        }
        
        if ($type === 'document') {
            return $query->whereIn('mime_type', [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'application/rtf',
                'text/rtf'
            ]);
        }
        
        return $query;
    }

    /**
     * Scope to filter files by parent model.
     */
    public function scopeForModel($query, $model)
    {
        return $query->where('fileable_type', get_class($model))
                    ->where('fileable_id', $model->id);
    }

    /**
     * Scope to filter files by upload status.
     */
    public function scopeByUploadStatus($query, string $status)
    {
        return $query->where('upload_status', $status);
    }

    /**
     * Scope to get only completed uploads.
     */
    public function scopeCompleted($query)
    {
        return $query->where('upload_status', 'completed');
    }

    /**
     * Scope to get only pending uploads.
     */
    public function scopePending($query)
    {
        return $query->where('upload_status', 'pending');
    }

    /**
     * Scope to get only failed uploads.
     */
    public function scopeFailed($query)
    {
        return $query->where('upload_status', 'failed');
    }

    /**
     * Check if file exists in storage.
     */
    public function existsInStorage(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Delete file from storage and database.
     */
    public function deleteFromStorage(): bool
    {
        if ($this->existsInStorage()) {
            Storage::disk($this->disk)->delete($this->path);
        }
        
        return $this->delete();
    }

    /**
     * Get the file contents.
     */
    public function getContents(): ?string
    {
        if (!$this->existsInStorage()) {
            return null;
        }
        
        return Storage::disk($this->disk)->get($this->path);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // When a file model is being deleted, also delete the physical file
        static::deleting(function ($file) {
            if ($file->existsInStorage()) {
                Storage::disk($file->disk)->delete($file->path);
            }
        });
    }
}