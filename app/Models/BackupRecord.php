<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupRecord extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'created_by', 'filename', 'path', 'disk', 'size', 'status',
        'checksum', 'completed_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedSizeAttribute(): string
    {
        $size = (float) $this->size;

        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($size < 1024) {
                return number_format($size, 2).' '.$unit;
            }
            $size /= 1024;
        }

        return number_format($size, 2).' TB';
    }
}
