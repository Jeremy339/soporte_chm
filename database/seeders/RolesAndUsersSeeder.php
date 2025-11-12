<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resetear roles y permisos cacheados para evitar errores ---
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 2. Crear los Roles ---
        // Usamos firstOrCreate para evitar duplicados
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'tecnico']);
        Role::firstOrCreate(['name' => 'usuario']);

        // --- 3. Crear el Usuario Administrador ---
        // Usamos firstOrCreate para encontrar por 'email' o crear uno nuevo
        $adminUser = User::firstOrCreate(
            [
                'email' => 'admin@proyecto.com' // Usamos email como identificador único
            ],
            [
                'name'   => 'Admin',
                'apellido1' => 'Principal',
                'cedula'    => '0000000001', // Asegúrate que sea único
                'email'     => 'admin@proyecto.com',
                'password'  => Hash::make('password123') 
                // 'telefono' y 'direccion' son nullables, así que no son necesarios aquí
            ]
        );
        $adminUser->assignRole('admin');

        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrCreate(
                ['email' => "tecnico{$i}@proyecto.com"],
                [
                    'name' => "Técnico {$i}",
                    'apellido1' => "Apellido{$i}",
                    'cedula' => str_pad($i + 1, 10, '0', STR_PAD_LEFT),
                    'password' => Hash::make('tecnico12'),
                ]
            );
            $user->assignRole('tecnico');
        }

        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrCreate(
                ['email' => "usuario{$i}@proyecto.com"],
                [
                    'name' => "Usuario {$i}",
                    'apellido1' => "Apellido{$i}",
                    'cedula' => str_pad($i + 100, 10, '0', STR_PAD_LEFT),
                    'password' => Hash::make('usuario12'),
                ]
            );
            $user->assignRole('usuario');
        }
    }
}