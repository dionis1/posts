<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Tag;
use App\Policies\TaxonomyPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Livewire::addPersistentMiddleware([AuthenticateSession::class]);
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::policy(Category::class, TaxonomyPolicy::class);
        Gate::policy(Tag::class, TaxonomyPolicy::class);
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip()));
        });
    }
}
