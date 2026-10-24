<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $screening_slot_id
 * @property int $application_id
 * @property string $kind
 * @property-read ScreeningSlot $slot
 * @property-read Application $application
 */
class ScreeningBooking extends Model
{
    protected $fillable = ['screening_slot_id', 'application_id', 'kind'];

    /** @return BelongsTo<ScreeningSlot, $this> */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(ScreeningSlot::class, 'screening_slot_id');
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
