<?php

namespace Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Recca0120\FilamentPermission\Components\PermissionCheckboxList;
use Recca0120\FilamentPermission\Facades\FilamentPermission;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\RoleResource\Pages\CreateRole;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\RoleResource\Pages\EditRole;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\RoleResource\Pages\ListRoles;
use Recca0120\FilamentPermission\Tests\Fixtures\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationGroup = 'Users';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->readOnly(fn (?Role $record) => $record?->name === 'Super Admin')
                                    ->required(),
                                Forms\Components\Select::make('guard_name')
                                    ->options(function () {
                                        $guards = array_keys(config('auth.guards'));

                                        return array_combine($guards, $guards);
                                    })
                                    ->default(config('auth.defaults.guard'))
                                    ->required(),
                                Forms\Components\Toggle::make('select_all')
                                    ->label('Select All')
                                    ->onIcon('heroicon-s-shield-check')
                                    ->offIcon('heroicon-s-shield-exclamation')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, bool $state) {
                                        $set('permissions', FilamentPermission::toggleAll($state));
                                    }),
                            ]),
                    ]),
                PermissionCheckboxList::make('permissions')
                    ->toggleAllCheckbox(fn (Forms\Set $set, bool $state) => $set('select_all', $state))
                    ->columns(['sm' => 2, 'lg' => 3]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->description(fn (Role $record) => $record->guard_name ?: 'web'),
                Tables\Columns\TextColumn::make('permissions_count')->counts('permissions'),
                Tables\Columns\TextColumn::make('users_count')->counts('users'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->checkIfRecordIsSelectableUsing(fn (Role $record) => RoleResource::canDelete($record))
            ->defaultSort('id')
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
