<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Resetear cache de roles y permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'tecnico']);
        Role::firstOrCreate(['name' => 'recepcionista']);

        // Usuario Administrador
        $adminUser = User::firstOrCreate(
            ['email' => 'soportechm62@gmail.com'],
            [
                'name' => 'Hernán',
                'apellido1' => 'Admin',
                'cedula' => '0171372440',
                'password' => Hash::make('@1713724407'),
            ]
        );

        $adminUser->assignRole('admin');

        // Usuario Técnico
        $tecnico = User::firstOrCreate(
            ['email' => 'mejia_hernan@hotmail.com'],
            [
                'name' => 'Hernán',
                'apellido1' => 'Mejía',
                'cedula' => '1713724407',
                'password' => Hash::make('1713724407'),
            ]
        );

        $tecnico->assignRole('tecnico');

        // Usuario Recepcionista
        $recepcionista = User::firstOrCreate(
            ['email' => 'taniapozo2@hotmail.com'],
            [
                'name' => 'Tania',
                'apellido1' => 'Pozo',
                'cedula' => '1714295001',
                'password' => Hash::make('1714295001'),
            ]
        );

        $recepcionista->assignRole('recepcionista');
    }
}
