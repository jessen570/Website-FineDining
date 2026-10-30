<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'actor_name',
        'actor_role',
        'action',
        'document_type',
        'document_id',
        'reference_code',
        'before_payload',
        'after_payload',
        'message',
        'created_at',
    ];

    protected $casts = [
        'before_payload' => 'array',
        'after_payload' => 'array',
        'created_at' => 'datetime',
    ];

    public static function log(
        string $action,
        string $docType,
        string $docId,
        ?string $referenceCode = null,
        $before = null,
        $after = null,
        ?string $message = null,
        ?string $actorName = null,
        ?string $actorRole = null
    ): self {
        return self::create([
            'actor_name' => $actorName ?? auth()->user()?->name ?? 'System User',
            'actor_role' => $actorRole ?? auth()->user()?->role ?? 'System',
            'action' => $action,
            'document_type' => $docType,
            'document_id' => $docId,
            'reference_code' => $referenceCode,
            'before_payload' => is_array($before) ? $before : (is_object($before) ? (array) $before : null),
            'after_payload' => is_array($after) ? $after : (is_object($after) ? (array) $after : null),
            'message' => $message,
            'created_at' => now(),
        ]);
    }
}

