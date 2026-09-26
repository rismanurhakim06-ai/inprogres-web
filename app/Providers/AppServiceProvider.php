<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.navigation', function (ViewInstance $view): void {
            $user = auth()->user();

            $view->with(
                'pendingAccountApprovalsCount',
                $user?->isAdmin()
                    ? User::query()->where('role', 'user')->where('is_approved', false)->count()
                    : 0,
            );
        });
    }
}
