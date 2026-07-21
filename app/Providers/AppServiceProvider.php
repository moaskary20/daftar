<?php

namespace App\Providers;

use App\Filament\Actions\DataTransferActions;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            $livewire = $table->getLivewire();

            if (! ($livewire instanceof ListRecords || $livewire instanceof ManageRecords)) {
                return;
            }

            $resource = $livewire::getResource();

            $table->pushHeaderActions(DataTransferActions::make(
                $resource,
                $resource::getModel(),
            ));
        });

        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            $action = match ($ability) {
                'viewAny', 'view' => 'view',
                'create', 'replicate' => 'create',
                'update', 'reorder', 'restore', 'restoreAny' => 'update',
                'delete', 'deleteAny', 'forceDelete', 'forceDeleteAny' => 'delete',
                default => null,
            };

            if (! $action || ! isset($arguments[0])) {
                return null;
            }

            $subject = $arguments[0];
            $modelClass = $subject instanceof Model ? $subject::class : $subject;

            if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true)) {
                return null;
            }

            $user->loadMissing('roles.permissions');

            if ($user->roles->contains(fn ($role): bool => $role->is_active && $role->slug === 'manager')) {
                return true;
            }

            $resource = Str::snake(Str::pluralStudly(class_basename($modelClass)));

            return $user->roles
                ->where('is_active', true)
                ->flatMap->permissions
                ->contains(fn ($permission): bool => $permission->resource === $resource
                    && $permission->action === $action);
        });
    }
}
