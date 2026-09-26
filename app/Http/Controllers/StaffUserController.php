<?php

namespace App\Http\Controllers;

use App\Models\RoleDashboardSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;

class StaffUserController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $creatableRoles = RoleDashboardSetting::creatableRolesFor($request->user()->role);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string', 'in:'.implode(',', array_keys($creatableRoles))],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        return redirect()->route('settings.roles.edit')->with('success', 'Akun dengan role '.$validated['role'].' berhasil dibuat.');
    }
}
