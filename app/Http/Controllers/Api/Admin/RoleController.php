<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $roles = Role::with('permissions')->get();
        return $this->success($roles);
    }

    public function permissions()
    {
        $permissions = Permission::all()->groupBy('group');
        return $this->success($permissions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return $this->success($role->load('permissions'), 'Role created successfully.', 201);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // Requirement: Super Admin cannot be edited
        if ($role->is_system || $role->slug === 'super-admin') {
            return $this->error('The Super Admin system role cannot be modified.', 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:roles,name,{$id}"],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return $this->success($role->load('permissions'), 'Role updated successfully.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // Requirement: Super Admin cannot be deleted
        if ($role->is_system || $role->slug === 'super-admin') {
            return $this->error('The Super Admin system role cannot be deleted.', 403);
        }

        if ($role->users()->count() > 0) {
            return $this->error('Cannot delete role that is currently assigned to users.', 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return $this->success(null, 'Role deleted successfully.');
    }
}
