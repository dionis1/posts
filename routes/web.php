<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn () => 'Posts — editorial workspace');

// Load the authentication, verification, and account-management routes.
require __DIR__.'/auth.php';
