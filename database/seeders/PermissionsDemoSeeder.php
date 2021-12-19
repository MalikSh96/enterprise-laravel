<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class PermissionsDemoSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $createPermissionOnUser = Permission::create(['name' => 'Create a new user']);
        $updatePermissionOnUser = Permission::create(['name' => 'Update an existing user']);
        $readPermissionOnUser = Permission::create(['name' => 'Get an existing user or users']);
        $deletePermissionOnUser = Permission::create(['name' => 'Delete or remove a user or users']);

        // create roles and assign existing permissions
        $allroundRole = Role::create(['name' => 'canCRUDonUser']);
        $allroundRole->givePermissionTo([
            $createPermissionOnUser, 
            $updatePermissionOnUser, 
            $readPermissionOnUser, 
            $deletePermissionOnUser
        ]);

        // create demo users
        $adminUser = User::factory()->create([
            'name' => 'Malik-Admin',
            'email' => 'admin@example.com',
            'password' =>  bcrypt("asd")
        ]);
        $adminUser->assignRole($allroundRole);
        $adminUser->givePermissionTo([
            $createPermissionOnUser, 
            $updatePermissionOnUser, 
            $readPermissionOnUser, 
            $deletePermissionOnUser
        ]);

        $nonAdminUser = User::factory()->create([
            'name' => 'Malik-Non-Admin',
            'email' => 'nonAdmin@example.com',
            'password' =>  bcrypt("asd")
        ]);
    }
}