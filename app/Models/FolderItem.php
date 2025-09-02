<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FolderItem extends Model
{
    protected $fillable = [
        'folder_id',
        'folderable_type',
        'folderable_id',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function folderable(): MorphTo
    {
        return $this->morphTo();
    }
}
