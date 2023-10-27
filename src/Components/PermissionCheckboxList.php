<?php

namespace Recca0120\FilamentPermission\Components;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

class PermissionCheckboxList extends Field
{
    protected string $view = 'filament-forms::components.grid';

    public static function isAll(Form $form, string $name): bool
    {
        return static::checkboxLists($form, $name)->flatMap(function (CheckboxList $checkboxList) {
            return collect($checkboxList->getEnabledOptions())->keys()->diff($checkboxList->getState());
        })->isEmpty();
    }

    public static function permissions(bool | array | Collection $state): array
    {
        if (is_bool($state)) {
            $permissions = $state ? Permission::all() : collect();
        } elseif (is_a($state, Collection::class) && is_a($state->first(), Permission::class)) {
            $permissions = $state;
        } else {
            $permissions = Permission::query()->whereIn('id', $state)->get();
        }

        return static::permissionGroupByPrefix($permissions)
            ->map(fn (Collection $group) => $group->pluck('id'))
            ->toArray();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
        $this->columnSpanFull();

        $permissions = Permission::query()->get();

        $this->schema(
            self::permissionGroupByPrefix($permissions)
                ->map(function (Collection $permissions) {
                    return $permissions->keyBy('id')->map(function (Permission $permission) {
                        return Str::of($permission->name)->after('.')->headline()->value();
                    });
                })
                ->map(function (Collection $options, string $entity) {
                    return Section::make(Str::ucfirst($entity))
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
                                ->afterStateUpdated(fn ($state) => $this->updateSelectAll())
                                ->bulkToggleable()
                                ->gridDirection('row')
                                ->columns(['sm' => 2, 'lg' => 3]),
                        ])
                        ->collapsible()
                        ->columnSpan(1);
                })
                ->toArray()
        );

        $this->mutateDehydratedStateUsing(function (Component $component) {
            return collect($component->getState())->collapse()->values()->toArray();
        });

        $this->afterStateHydrated(function (Component $component, ?Model $record) {
            if (! $record) {
                return;
            }

            $state = static::checkboxLists($this->getForm(), $this->getName())
                ->flatMap(fn (CheckboxList $checkboxList) => array_keys($checkboxList->getEnabledOptions()))
                ->diff($record->permissions->pluck('id'))
                ->isEmpty();

            $this->selectAll()->state($state);
        });

        $this->saveRelationshipsUsing(function (Model $record, ?array $state) {
            $currentState = collect($state)->collapse();
            if (is_a($record, HasRoles::class)) {
                $currentState = $currentState->diff($record->getPermissionsViaRoles()->pluck('id'));
            }
            $record->syncPermissions($currentState->values()->toArray());
        });

        $this->loadStateFromRelationshipsUsing(function (Component $component, ?Model $record) {
            if (! $record) {
                return $component->state([]);
            }

            $state = static::permissionGroupByPrefix($record->getAllPermissions())
                ->map(fn (Collection $permissions) => $permissions->pluck('id'))
                ->toArray();

            return $component->state($state);
        });
    }

    private function toggleCheckboxList(CheckboxList $component, bool $state, $update = false): array
    {
        $currentState = $state ? array_keys($component->getEnabledOptions()) : [];
        $component->state($currentState);
        if ($update === true) {
            $this->updateSelectAll();
        }

        return $currentState;
    }

    private function updateSelectAll(): void
    {
        $this->selectAll()->state(static::isAll($this->getForm(), $this->getName()));
    }

    private function selectAll(): Component
    {
        return static::getField($this->getForm(), 'select_all');
    }

    private function getForm(): Form
    {
        return $this->getLivewire()->getForm('form');
    }

    private static function permissionGroupByPrefix(Collection $permissions): Collection
    {
        return $permissions->groupBy(function (Permission $permission) {
            return Str::of($permission->name)->before('.')->value();
        });
    }

    private static function checkboxLists(Form $form, string $name): Collection
    {
        return collect(static::getField($form, $name)->getChildComponents())
            ->flatMap(fn (Component $component) => $component->getChildComponents());
    }

    private static function getField(Form $form, string $name): Component
    {
        return collect($form->getFlatComponents())
            ->filter(fn (Component $component) => is_a($component, Field::class))
            ->first(fn (Field $select) => $select->getName() === $name);
    }
}
