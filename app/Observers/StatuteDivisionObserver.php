<?php

namespace App\Observers;

use App\Models\StatuteDivision;
use Illuminate\Support\Facades\Cache;

class StatuteDivisionObserver
{
    /**
     * Handle the StatuteDivision "created" event.
     */
    public function created(StatuteDivision $statuteDivision): void
    {
        $this->invalidateCaches($statuteDivision);
    }

    /**
     * Handle the StatuteDivision "updated" event.
     */
    public function updated(StatuteDivision $statuteDivision): void
    {
        $this->invalidateCaches($statuteDivision);
    }

    /**
     * Handle the StatuteDivision "deleted" event.
     */
    public function deleted(StatuteDivision $statuteDivision): void
    {
        $this->invalidateCaches($statuteDivision);
    }

    /**
     * Handle the StatuteDivision "restored" event.
     */
    public function restored(StatuteDivision $statuteDivision): void
    {
        $this->invalidateCaches($statuteDivision);
    }

    /**
     * Handle the StatuteDivision "force deleted" event.
     */
    public function forceDeleted(StatuteDivision $statuteDivision): void
    {
        $this->invalidateCaches($statuteDivision);
    }

    /**
     * Invalidate related caches
     */
    private function invalidateCaches(StatuteDivision $statuteDivision): void
    {
        if ($statuteDivision->statute_id) {
            // Clear all caches related to this statute (breadcrumbs, position metadata, total items)
            Cache::tags(["statute:{$statuteDivision->statute_id}"])->flush();
        }
    }
}
