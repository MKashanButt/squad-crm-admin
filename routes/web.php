<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

// Impersonation routes
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/impersonate/take/{user}', function (User $user) {
        if (!auth()->user()->canImpersonate()) {
            abort(403, 'You are not allowed to impersonate users');
        }

        if (!$user->canBeImpersonated()) {
            abort(403, 'This user cannot be impersonated');
        }

        auth()->user()->impersonate($user);
        return redirect('/'); // Redirect to home or dashboard
    })->name('impersonate');

    Route::get('/impersonate/leave', function () {
        if (!auth()->user()->isImpersonating()) {
            return redirect('/');
        }

        auth()->user()->leaveImpersonation();
        return redirect('/admin'); // Redirect back to Filament admin
    })->name('impersonate.leave');
});
