<?php

namespace App\Http\Controllers;

use App\Models\RoleDashboardSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('settings.accounts', [
            'users' => User::query()->orderBy('name')->paginate(20),
            'roleLabels' => RoleDashboardSetting::ROLE_LABELS,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(array_keys(RoleDashboardSetting::ROLE_LABELS))],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('settings.accounts.index')->with('success', 'Akun '.$user->email.' berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 403, 'Akun yang sedang digunakan tidak dapat dihapus.');

        $email = $user->email;
        $user->delete();

        return redirect()->route('settings.accounts.index')->with('success', 'Akun '.$email.' berhasil dihapus.');
    }
}
