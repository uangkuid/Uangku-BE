<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Jejak audit aksi staff (immutable — tidak ada updated_at).
 *
 * Catat aksi sensitif via AuditLog::record(). Simpan HANYA metadata non-sensitif
 * (zero-knowledge: jangan pernah menaruh nominal/isi transaksi/private key di sini).
 */
class AuditLog extends BaseModel
{
    use HasUuids;

    /** Log tidak pernah di-update; matikan updated_at. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'staff_id',
        'action',
        'target_type',
        'target_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffAccount::class, 'staff_id');
    }

    /**
     * Catat satu aksi audit. Aktor & konteks request diisi otomatis.
     *
     * @param  Model|null  $target  Record yang dikenai aksi (opsional).
     * @param  array<string,mixed>  $metadata  Konteks tambahan NON-sensitif.
     */
    public static function record(
        string $action,
        ?Model $target = null,
        array $metadata = [],
        ?string $description = null,
    ): self {
        return static::create([
            'staff_id' => Auth::guard('web')->id(),
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
