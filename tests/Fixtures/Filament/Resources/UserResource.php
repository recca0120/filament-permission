<?php

namespace Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources;

use Recca0120\FilamentPermission\Tests\Fixtures\Models\Role;
use Recca0120\FilamentPermission\Tests\Fixtures\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Recca0120\FilamentPermission\Components\PermissionCheckboxList;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\UserResource\Pages\CreateUser;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\UserResource\Pages\EditUser;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\UserResource\Pages\ListUsers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Users';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(table: User::class, column: 'email', ignoreRecord: true)
                            ->required(),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(static fn(string $context) => $context === 'create')
                            ->dehydrated(fn($state) => filled($state)),
                        Forms\Components\Select::make('roles')
                            ->relationship(name: 'roles', titleAttribute: 'name')
                            ->multiple()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (CreateUser|EditUser $livewire, Forms\Set $set, $state) {
                                $permissions = Role::query()
                                    ->whereIn('id', $state)
                                    ->get()
                                    ->flatMap(fn(Role $role) => $role->getAllPermissions())
                                    ->unique();

                                $set('permissions', PermissionCheckboxList::permissions($permissions));
                                $set('select_all', PermissionCheckboxList::isAll($livewire->form, 'permissions'));
                            }),
                        Forms\Components\Toggle::make('select_all')
                            ->label('Select All')
                            ->onIcon('heroicon-s-shield-check')
                            ->offIcon('heroicon-s-shield-exclamation')
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, bool $state) {
                                $set('permissions', PermissionCheckboxList::permissions($state));
                            }),
                    ]),
                PermissionCheckboxList::make('permissions')->columns(['sm' => 2, 'lg' => 3]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
