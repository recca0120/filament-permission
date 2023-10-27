<?php

namespace Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\RoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\RoleResource;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
