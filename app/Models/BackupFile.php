<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupFile extends Model
{
    protected $fillable = [
        'backup_id',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
    ];

    public function backup(): BelongsTo
    {
        return $this->belongsTo(Backup::class);
    }
}
