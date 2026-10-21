<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EditorialController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\TaxonomyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicationController::class, 'index'])->name('publication.index');
Route::get('/read/{slug}', [PublicationController::class, 'show'])->name('publication.show');
Route::get('/sitemap.xml', [PublicationController::class, 'sitemap'])->name('publication.sitemap');

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

    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->middleware('throttle:10,1')->name('media.store');
    Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->middleware('throttle:5,1')->name('comments.store');
    Route::patch('/comments/{comment}/moderate', [CommentController::class, 'moderate'])->name('comments.moderate');

    Route::get('/taxonomy', [TaxonomyController::class, 'index'])->name('taxonomy.index');
    Route::post('/taxonomy/{type}', [TaxonomyController::class, 'store'])->name('taxonomy.store');
    Route::delete('/taxonomy/{type}/{id}', [TaxonomyController::class, 'destroy'])->name('taxonomy.destroy');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

require __DIR__.'/auth.php';
