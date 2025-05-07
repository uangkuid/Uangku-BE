<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyInvitation extends Model
{
    use HasFactory, HasUuids;


    protected $fillable = [
        'family',
        'inviter_id',
        'invitee_id',
        'status',
        'expired_at',
    ];
}
