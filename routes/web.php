<?php

use App\Http\Controllers\AccountApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleDashboardSettingsController;
use App\Http\Controllers\StaffUserController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TicketController::class, 'index'])->name('home');
Route::post('/tickets', [TicketController::class, 'store'])
    ->middleware(['auth', 'role:user'])
    ->name('tickets.store');
Route::get('/admin', fn () => redirect()->route('login'))->name('admin.login');

Route::middleware(['auth', 'role:owner,admin,supervisor,user'])->group(function () {
    Route::get('/dashboard', [TicketController::class, 'dashboard'])->name('dashboard');
});

Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    Route::get('/pengaturan/persetujuan-akun', [AccountApprovalController::class, 'index'])->name('account-approvals.index');
    Route::post('/pengaturan/persetujuan-akun/{user}', [AccountApprovalController::class, 'store'])->name('account-approvals.store');
    Route::get('/pengaturan/tampilan-role', [RoleDashboardSettingsController::class, 'index'])->name('settings.roles.edit');
    Route::post('/pengaturan/tampilan-role/akun', [StaffUserController::class, 'store'])->name('settings.roles.users.store');
});

Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::put('/pengaturan/tampilan-role', [RoleDashboardSettingsController::class, 'update'])->name('settings.roles.update');
    Route::get('/pengaturan/akun', [UserManagementController::class, 'index'])->name('settings.accounts.index');
    Route::put('/pengaturan/akun/{user}', [UserManagementController::class, 'update'])->name('settings.accounts.update');
    Route::delete('/pengaturan/akun/{user}', [UserManagementController::class, 'destroy'])->name('settings.accounts.destroy');
});

Route::middleware(['auth', 'role:owner,admin,user'])->group(function () {
    Route::get('/tickets/{ticket}/comments', [TicketController::class, 'comments'])->name('tickets.comments');
    Route::post('/tickets/{ticket}/comments', [TicketController::class, 'storeComment'])->name('tickets.comments.store');
});

Route::middleware(['auth', 'role:owner,admin,supervisor'])->group(function () {
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
});

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
    Route::patch('/tickets/{ticket}/submission', [TicketController::class, 'updateOwn'])->name('tickets.update-own');
});

Route::middleware(['auth', 'role:supervisor'])->group(function () {
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/akun', [ProfileController::class, 'edit'])->name('account.edit');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
