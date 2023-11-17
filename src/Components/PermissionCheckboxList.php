<?php

namespace Recca0120\FilamentPermission\Components;

use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Recca0120\FilamentPermission\FilamentPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class PermissionCheckboxList extends Field
{
    protected string $view = 'filament-forms::components.grid';

    private ?Closure $toggleAllCheckboxUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
        $this->columnSpanFull();

        $permissions = Permission::query()->get();

        $this->schema(
            FilamentPermission::permissionGroupByPrefix($permissions)
                ->map(fn (Collection $permissions) => $permissions
                    ->keyBy('id')
                    ->map(fn ($permission) => $this->translate('labels', Str::after($permission->name, '.'))))
                ->map(function (Collection $options, string $entity) {
                    return Section::make(fn () => $this->translate('entities', $entity))
                        ->compact()
                        ->schema([
                            CheckboxList::make($entity)->label('')
                                ->options($options)
                                ->live()
                                ->selectAllAction(fn (Action $action, CheckboxList $component) => $action
                                    ->livewireClickHandlerEnabled()
                                    ->action(fn () => $this->toggleCheckboxList($component, true, true)))
                                ->deselectAllAction(fn (Action $action, CheckboxList $component) => $action
                                    ->livewireClickHandlerEnabled()
                                    ->action(fn () => $this->toggleCheckboxList($component, false, true)))
                                ->afterStateUpdated(fn () => $this->callToggleAllCheckboxUsing())
                                ->bulkToggleable()
                                ->gridDirection('row')
                                ->columns(['sm' => 2, 'lg' => 3]),
                        ])
                        ->collapsible()
                        ->columnSpan(1);
                })
                ->toArray()
        );

        $this->afterStateHydrated(function (Component $component, ?Model $record) {
            if ($record) {
                $this->callToggleAllCheckboxUsing();
            }
        });

        $this->saveRelationshipsUsing(function (/** @var HasPermissions $record */ Model $record, ?array $state) {
            $record->syncPermissions(value(static function (Collection $state) use ($record) {
                return in_array(HasRoles::class, class_uses_recursive($record), true)
                    ? $state->diff($record->getPermissionsViaRoles()->pluck('id'))
                    : $state;
            }, collect($state ?? [])->collapse()->map(fn (int $id) => $id))->values());
        });

        $this->loadStateFromRelationshipsUsing(function (Component $component, ?Model $record) {
            return $component->state(! $record ? [] : FilamentPermission::permissionGroupByPrefix($record->getAllPermissions())
                ->map(fn (Collection $permissions) => $permissions->pluck('id'))
                ->toArray());
        });
    }

    private function toggleCheckboxList(CheckboxList $component, bool $state): void
    {
        $currentState = $state ? array_keys($component->getEnabledOptions()) : [];
        $component->state($currentState);
        $this->callToggleAllCheckboxUsing();
    }

    public function toggleAllCheckbox(?Closure $callback): static
    {
        $this->toggleAllCheckboxUsing = $callback;

        return $this;
    }

    public function callToggleAllCheckboxUsing(): void
    {
        if ($this->toggleAllCheckboxUsing) {
            $this->evaluate($this->toggleAllCheckboxUsing, [
                'state' => $this->checkIfAllCheckboxesAreChecked(),
            ]);
        }
    }

    private function checkboxLists(): Collection
    {
        return collect($this->getChildComponentContainer()->getFlatComponents())
            ->filter(fn (Component $component) => is_a($component, CheckboxList::class));
    }

    private function checkIfAllCheckboxesAreChecked(): bool
    {
        return $this->checkboxLists()->flatMap(function (CheckboxList $checkboxList) {
            return collect($checkboxList->getEnabledOptions())->keys()->diff($checkboxList->getState());
        })->isEmpty();
    }

    private function translate(string $section, string $label): string
    {
        /** @var Translator $translator */
        $translator = app('translator');
        $key = 'filament-permission::permission.' . $section . '.' . $label;
        if ($translator->has($key)) {
            return $translator->get($key);
        }

        return Str::of($label)->headline();
    }
}
