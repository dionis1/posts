<?php

use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'verified', 'throttle:api'])->group(function () {
    Route::get('/posts', [PostController::class, 'index'])->name('api.v1.posts.index');
    Route::post('/posts', [PostController::class, 'store'])->name('api.v1.posts.store');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('api.v1.posts.show');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('api.v1.posts.update');
    Route::post('/posts/{post}/transitions', [PostController::class, 'transition'])->name('api.v1.posts.transition');
    Route::post('/posts/{post}/revisions/{revision}/restore', [PostController::class, 'restore'])->name('api.v1.posts.restore');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('api.v1.posts.destroy');
});
