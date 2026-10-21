<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMedia;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\VerifySecondFactor;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

it('never authenticates a two factor account before its challenge is verified', function () {
    $totp = app(Google2FA::class);
    $secret = $totp->generateSecretKey();
    $user = User::factory()->create(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'password' => 'a-test-password-123']);
    $this->post('/login', ['email' => $user->email, 'password' => 'a-test-password-123'])->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();
    $this->get('/posts')->assertRedirect(route('login'));
    $this->post('/two-factor-challenge', ['code' => $totp->getCurrentOtp($secret)])->assertRedirect(route('posts.index'));
    $this->assertAuthenticatedAs($user);
});

it('rejects reused totp and consumes recovery codes once', function () {
    $secret = app(Google2FA::class)->generateSecretKey();
    $user = User::factory()->create(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => [Hash::make('single-use-recovery')]]);
    $verifier = app(VerifySecondFactor::class);
    $code = app(Google2FA::class)->getCurrentOtp($secret);
    expect($verifier->handle($user, $code))->toBeTrue()->and($verifier->handle($user, $code))->toBeFalse()
        ->and($verifier->handle($user, 'single-use-recovery'))->toBeTrue()->and($verifier->handle($user, 'single-use-recovery'))->toBeFalse();
    expect($user->fresh()->two_factor_recovery_codes)->toBe([]);
});

it('expires a pending second factor login', function () {
    $secret = app(Google2FA::class)->generateSecretKey();
    $user = User::factory()->create(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'password' => 'a-test-password-123']);
    $this->post('/login', ['email' => $user->email, 'password' => 'a-test-password-123']);
    $this->travel(6)->minutes();
    $this->post('/two-factor-challenge', ['code' => app(Google2FA::class)->getCurrentOtp($secret)])->assertRedirect(route('login'));
    $this->assertGuest();
});

it('validates image uploads and queues only accepted files', function () {
    Storage::fake('local');
    Queue::fake();
    $this->actingAs(User::factory()->create());
    $this->post('/media', ['image' => UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('image');
    expect(Media::count())->toBe(0);
    $this->post('/media', ['image' => UploadedFile::fake()->image('cover.png', 64, 64)])->assertSessionHasNoErrors();
    $media = Media::firstOrFail();
    expect($media->status)->toBe(MediaStatus::Pending);
    Queue::assertPushed(ProcessMedia::class);
    Storage::disk('local')->assertExists($media->path);
});

it('reencodes uploaded images idempotently before they can be served', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $upload = UploadedFile::fake()->image('photo.png', 64, 64);
    $path = $upload->store('media/incoming', 'local');
    $media = Media::create(['user_id' => $user->id, 'path' => $path, 'original_name' => 'photo.png', 'mime' => 'image/png', 'size' => 100, 'status' => MediaStatus::Pending]);
    $job = new ProcessMedia($media->id);
    $job->handle();
    $job->handle();
    $media->refresh();
    expect($media->status)->toBe(MediaStatus::Ready)->and($media->mime)->toBe('image/png');
    Storage::disk('local')->assertExists($media->path);
    Storage::disk('local')->assertExists($media->thumbnail_path);
    Storage::disk('local')->assertMissing($path);
});

it('enforces the lazy loading guard during development and tests', function () {
    Post::factory()->count(2)->create();
    $posts = Post::get();
    expect(fn () => $posts->first()->author)->toThrow(LazyLoadingViolationException::class);
});
