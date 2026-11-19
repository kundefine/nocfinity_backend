<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $staff = User::whereHas('roles')
            ->with('roles')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return $this->paginated($staff);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        $user->roles()->sync($validated['roles']);

        return $this->success($user->load('roles'), 'Staff member created successfully.', 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', "unique:users,email,{$id}"],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        // Prevent removing super-admin role from the last super admin
        if ($user->isSuperAdmin() && isset($validated['roles'])) {
            // keep super-admin role
            $superAdminRoleId = \App\Models\Role::where('slug', 'super-admin')->value('id');
            if (!in_array($superAdminRoleId, $validated['roles'])) {
                $validated['roles'][] = $superAdminRoleId;
            }
        }

        $user->update($data);

        if (isset($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        return $this->success($user->load('roles'), 'Staff member updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Requirement: Super Admin cannot be deleted
        if ($user->isSuperAdmin()) {
            return $this->error('The Super Admin account cannot be deleted.', 403);
        }

        $user->roles()->detach();
        $user->delete();

        return $this->success(null, 'Staff member deleted successfully.');
    }
}
