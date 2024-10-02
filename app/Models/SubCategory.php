<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name'
    ];

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class, 'categories');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'users');
    }
}
