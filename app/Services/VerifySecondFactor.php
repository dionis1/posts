<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class VerifySecondFactor
{
    public function handle(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            if (! $user->two_factor_secret) {
                return false;
            }
            $timestamp = app(Google2FA::class)->verifyKeyNewer($user->two_factor_secret, $code, $user->two_factor_last_used ?? 0, 1);
            if ($timestamp !== false) {
                $user->forceFill(['two_factor_last_used' => $timestamp])->save();

                return true;
            }
            if (! $user->two_factor_confirmed_at) {
                return false;
            }
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (Hash::check($code, $hash)) {
                    unset($codes[$index]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                    return true;
                }
            }

            return false;
        });
    }
}
