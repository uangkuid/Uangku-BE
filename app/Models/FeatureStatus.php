<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureStatus extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'feature_name',
        'is_enabled',
        'updated_by',
    ];

    public function staffs(): BelongsTo {
        return $this->belongsTo(StaffAccount::class, 'updated_by');
    }
}
