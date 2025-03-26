<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Verificar si el rol admin existe
        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $adminRole = Role::create(['name' => 'admin']);
        }

        // Crear usuario admin
        $admin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('12345678'), // Cambia la contraseña después
        ]);

        // Asignar rol de administrador
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        echo "Usuario admin creado: admin@example.com / 12345678\n";
    }
}
