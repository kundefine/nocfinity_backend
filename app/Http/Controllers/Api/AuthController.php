<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRules;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', PasswordRules::min(8)->letters()->numbers()],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        $token = $user->createToken('storefront-auth-token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'token' => $token,
        ], 'Registration successful.', 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        // Requirement: Rate limiting on login attempts (throttle after 5 failed attempts)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->error("Too many login attempts. Please try again in {$seconds} seconds.", 429);
        }

        $user = User::where('email', $request->email)->first();

        // Requirement: Generic error message on failed login (never reveal whether email or password was wrong)
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return $this->error('Invalid email or password.', 401);
        }

        if (!$user->is_active) {
            return $this->error('Your account has been deactivated. Please contact support.', 403);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken('storefront-auth-token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'addresses' => $user->addresses ?? [],
                'roles' => $user->roles->pluck('slug'),
            ],
            'token' => $token,
        ], 'Login successful.');
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'admin|'.Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->error("Too many failed admin login attempts. Please wait {$seconds} seconds.", 429);
        }

        $user = User::with('roles.permissions')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            return $this->error('Invalid credentials.', 401);
        }

        if (!$user->is_active) {
            return $this->error('Your admin account has been deactivated.', 403);
        }

        // Verify staff/admin role
        if ($user->roles->isEmpty() && !$user->isSuperAdmin()) {
            return $this->error('Access denied. You do not have staff permissions.', 403);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken('admin-panel-token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'roles' => $user->roles->pluck('name', 'slug'),
                'is_super_admin' => $user->isSuperAdmin(),
                'permissions' => $user->getEffectivePermissions(),
            ],
            'token' => $token,
        ], 'Admin authentication successful.');
    }

    public function adminMe(Request $request)
    {
        $user = $request->user();
        if (!$user && $token = $request->bearerToken()) {
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
        }

        if (!$user) {
            return $this->error('Unauthenticated.', 401);
        }

        $user->load('roles.permissions');

        if ($user->roles->isEmpty() && !$user->isSuperAdmin()) {
            return $this->error('Access denied. Staff role required.', 403);
        }

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'roles' => $user->roles->pluck('name', 'slug'),
            'is_super_admin' => $user->isSuperAdmin(),
            'permissions' => $user->getEffectivePermissions(),
        ]);
    }

    public function adminLogout(Request $request)
    {
        $user = $request->user();
        if (!$user && $token = $request->bearerToken()) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            $tokenModel?->delete();
        } else {
            $request->user()?->currentAccessToken()?->delete();
        }

        return $this->success(null, 'Admin logged out successfully.');
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles.permissions');

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'addresses' => $user->addresses ?? [],
            'roles' => $user->roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'slug' => $r->slug]),
            'is_super_admin' => $user->isSuperAdmin(),
            'permissions' => $user->getEffectivePermissions(),
        ]);
    }

    public function updateAddresses(Request $request)
    {
        $validated = $request->validate([
            'addresses' => ['present', 'array'],
            'addresses.*.id' => ['nullable', 'string'],
            'addresses.*.title' => ['nullable', 'string'],
            'addresses.*.type' => ['nullable', 'string'],
            'addresses.*.label' => ['nullable', 'string'],
            'addresses.*.recipient_name' => ['nullable', 'string'],
            'addresses.*.recipient_phone' => ['nullable', 'string'],
            'addresses.*.address' => ['required', 'string', 'max:500'],
            'addresses.*.city' => ['required', 'string', 'max:100'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['nullable', 'string', 'max:100'],
            'addresses.*.is_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        if (!$user && $token = $request->bearerToken()) {
            $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;
        }

        if (!$user) {
            return $this->error('Unauthenticated.', 401);
        }

        $user->update(['addresses' => $validated['addresses']]);

        return $this->success($user->addresses, 'Addresses updated successfully.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'string'],
        ]);

        $user->update($validated);

        return $this->success($user, 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', PasswordRules::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('The provided current password is incorrect.', 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success(null, 'Password updated successfully.');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink(
            $request->only('email')
        );

        // Security best practice: Always return generic success message to prevent account enumeration
        return $this->success(null, 'If an account exists for this email, password reset instructions have been sent.');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', PasswordRules::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, __($status));
        }

        return $this->error(__($status), 422);
    }
}
