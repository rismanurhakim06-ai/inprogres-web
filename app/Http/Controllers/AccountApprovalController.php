<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountApprovalController extends Controller
{
    public function index(): View
    {
        $pendingUsers = User::query()
            ->where('role', 'user')
            ->where('is_approved', false)
            ->oldest()
            ->get();

        return view('settings.account-approvals', ['pendingUsers' => $pendingUsers]);
    }

    public function store(User $user): RedirectResponse
    {
        abort_unless($user->role === 'user', 404);

        $user->update(['is_approved' => true]);

        return redirect()->route('account-approvals.index')
            ->with('success', 'Akun '.$user->name.' berhasil disetujui.');
    }
}
