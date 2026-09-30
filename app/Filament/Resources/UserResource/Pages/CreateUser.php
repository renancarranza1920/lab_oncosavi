<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Spatie\Permission\Models\Role as SpatieRole;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): User
    {
        // Crear el usuario
        $user = User::create([
            'name' => $data['name'],
            'nickname' => $data['nickname'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'firma_path' => $data['firma_path'] ?? null,
            'sello_path' => $data['sello_path'] ?? null,
        ]);

        // Asignar el rol si está presente
        if (isset($data['roles'])) {
            $role = \Spatie\Permission\Models\Role::find($data['roles']);
            if ($role) {
                $user->syncRoles([$role->name]);
            }
        }

        return $user;
    }
}