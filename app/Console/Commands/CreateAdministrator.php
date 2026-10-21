<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'posts:admin {email : Email of the administrator}';

    protected $description = 'Create a verified administrator or promote an existing account';

    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $validator = Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:254']]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            if (! $this->confirm('Promote '.$email.' to administrator and mark this address verified?')) {
                return self::FAILURE;
            }
        } else {
            $name = $this->ask('Administrator name');
            $password = $this->secret('Password (at least 12 characters)');
            $confirmation = $this->secret('Confirm password');
            $validator = Validator::make(['name' => $name, 'password' => $password, 'password_confirmation' => $confirmation], [
                'name' => ['required', 'string', 'max:120'],
                'password' => ['required', 'confirmed', Password::min(12)->max(128)],
            ]);
            if ($validator->fails()) {
                $this->error($validator->errors()->first());

                return self::FAILURE;
            }
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        }
        $user->forceFill(['role' => 'admin', 'email_verified_at' => now()])->save();
        $this->info('Administrator ready: '.$email);

        return self::SUCCESS;
    }
}
