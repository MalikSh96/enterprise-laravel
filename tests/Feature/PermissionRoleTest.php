<?php

namespace Tests\Feature;

use Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class PermissionRoleTest extends TestCase
{
    private Permission $permission;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permission = Permission::firstOrCreate(['name' => 'test permission that will be given to role']);
        $this->role = Role::firstOrCreate(['name' => 'testRoleThatWillGetPermission']);
    }

    public function test_that_a_role_gets_a_permission()
    {
        $this->permission = Permission::where('id', $this->permission->id)->firstOrFail(); 
        $this->role = Role::where('id', $this->role->id)->firstOrFail();
        $this->role->givePermissionTo($this->permission);
        
        $this->assertDatabaseHas('role_has_permissions', [
            'role_id' => $this->role->id,
            'permission_id' => $this->permission->id
        ]);
    }
    
    public function test_that_permission_gets_revoked_from_role()
    {
        $this->role->revokePermissionTo($this->permission);
        
        $this->assertDatabaseMissing('role_has_permissions', [
            'permission_id' => $this->permission->id
        ]);
    }
}
