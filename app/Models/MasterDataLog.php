<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MasterDataLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'logs_masterdata';

    /**
     * Mass-assignment protection.
     */
    protected $fillable = [
        'user_id',
        'loggable_type',
        'loggable_id',
        'action',
        'details',
    ];

    /**
     * Cast 'details' ke array/object, bukan string JSON.
     */
    protected $casts = [
        'details' => 'array',
    ];

    /**
     * Relasi polimorfik ke model apapun (ManPower, Henkaten, dll).
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relasi ke User yang melakukan aksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
