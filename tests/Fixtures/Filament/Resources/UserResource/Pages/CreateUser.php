<?php

namespace Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Recca0120\FilamentPermission\Tests\Fixtures\Filament\Resources\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
