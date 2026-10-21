<?php

use App\Http\Controllers\EditorialController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [EditorialController::class, 'index'])->name('dashboard');
    Route::get('/posts', [EditorialController::class, 'index'])->name('posts.index');
    Route::get('/posts/create', [EditorialController::class, 'create'])->name('posts.create');
    Route::post('/posts', [EditorialController::class, 'store'])->name('posts.store');
    Route::post('/posts/preview', [EditorialController::class, 'preview'])->name('posts.preview');
    Route::get('/posts/{post}/edit', [EditorialController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{post}', [EditorialController::class, 'update'])->name('posts.update');
    Route::post('/posts/{post}/transition', [EditorialController::class, 'transition'])->name('posts.transition');
    Route::get('/posts/{post}/revisions', [EditorialController::class, 'revisions'])->name('posts.revisions');
    Route::post('/posts/{post}/revisions/{revision}/restore', [EditorialController::class, 'restore'])->name('posts.restore');
    Route::get('/posts/{post}/activity', [EditorialController::class, 'activity'])->name('posts.activity');
    Route::get('/calendar', [EditorialController::class, 'calendar'])->name('calendar.index');
});

require __DIR__.'/auth.php';
