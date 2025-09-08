<?php

use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Everyone with 'admin' middleware already passes.
    Gate::define('manage-settings', fn($user) => method_exists($user, 'isAdmin') ? $user->isAdmin() : true);
}