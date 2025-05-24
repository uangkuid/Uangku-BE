<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureStatus extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'feature_name',
        'is_enabled',
        'updated_by',
    ];
}
