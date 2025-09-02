<?php

namespace App\Traits;

use App\Models\Folder;
use App\Models\FolderItem;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait Folderable
{
    public function folderItems(): MorphMany
    {
        return $this->morphMany(FolderItem::class, 'folderable');
    }

    public function folders(): BelongsToMany
    {
        return $this->belongsToMany(Folder::class, 'folder_items', 'folderable_id', 'folder_id')
                    ->where('folder_items.folderable_type', static::class)
                    ->withTimestamps();
    }

    public function addToFolder(Folder $folder): bool
    {
        if ($this->isInFolder($folder)) {
            return false;
        }

        $this->folderItems()->create([
            'folder_id' => $folder->id,
        ]);

        return true;
    }

    public function removeFromFolder(Folder $folder): bool
    {
        return $this->folderItems()
                   ->where('folder_id', $folder->id)
                   ->delete() > 0;
    }

    public function isInFolder(Folder $folder): bool
    {
        return $this->folderItems()
                   ->where('folder_id', $folder->id)
                   ->exists();
    }

    public function removeFromAllFolders(): int
    {
        return $this->folderItems()->delete();
    }

    public function getFoldersList(): array
    {
        return $this->folders()
                   ->orderBy('name')
                   ->pluck('name', 'id')
                   ->toArray();
    }

    public function getFoldersCount(): int
    {
        return $this->folderItems()->count();
    }

    public function scopeInFolder($query, $folderId)
    {
        return $query->whereHas('folderItems', function ($q) use ($folderId) {
            $q->where('folder_id', $folderId);
        });
    }

    public function scopeInFolders($query, array $folderIds)
    {
        return $query->whereHas('folderItems', function ($q) use ($folderIds) {
            $q->whereIn('folder_id', $folderIds);
        });
    }

    public function scopeNotInAnyFolder($query)
    {
        return $query->whereDoesntHave('folderItems');
    }
}