<?php

namespace App\Observers;

use App\Models\StatuteProvision;
use Illuminate\Support\Facades\Cache;

class StatuteProvisionObserver
{
    /**
     * Handle the StatuteProvision "created" event.
     */
    public function created(StatuteProvision $statuteProvision): void
    {
        $this->invalidateCaches($statuteProvision);
    }

    /**
     * Handle the StatuteProvision "updated" event.
     */
    public function updated(StatuteProvision $statuteProvision): void
    {
        $this->invalidateCaches($statuteProvision);
    }

    /**
     * Handle the StatuteProvision "deleted" event.
     */
    public function deleted(StatuteProvision $statuteProvision): void
    {
        $this->invalidateCaches($statuteProvision);
    }

    /**
     * Handle the StatuteProvision "restored" event.
     */
    public function restored(StatuteProvision $statuteProvision): void
    {
        $this->invalidateCaches($statuteProvision);
    }

    /**
     * Handle the StatuteProvision "force deleted" event.
     */
    public function forceDeleted(StatuteProvision $statuteProvision): void
    {
        $this->invalidateCaches($statuteProvision);
    }

    /**
     * Invalidate related caches
     */
    private function invalidateCaches(StatuteProvision $statuteProvision): void
    {
        if ($statuteProvision->statute_id) {
            // Clear all caches related to this statute (breadcrumbs, position metadata, total items)
            Cache::tags(["statute:{$statuteProvision->statute_id}"])->flush();
        }
    }
}
