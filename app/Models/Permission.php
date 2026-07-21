<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Permission extends Model
{
    public const ACTION_VIEW = 'view';

    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DELETE = 'delete';

    protected $fillable = ['resource', 'action', 'name'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function getResourceLabelAttribute(): string
    {
        return Str::after($this->name, ' ');
    }

    public static function actionLabels(): array
    {
        return [
            self::ACTION_VIEW => 'عرض',
            self::ACTION_CREATE => 'إضافة',
            self::ACTION_UPDATE => 'تعديل',
            self::ACTION_DELETE => 'حذف',
        ];
    }
}
