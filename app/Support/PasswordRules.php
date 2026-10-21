<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class PasswordRules
{
    public static function confirmed(): array
    {
        return ['required', 'string', 'confirmed', Password::min(12)->max(128), function (string $attribute, mixed $value, \Closure $fail) {
            if (is_string($value) && (str_contains($value, "\0") || (config('hashing.driver') === 'bcrypt' && strlen($value) > 72))) {
                $fail(__('The password must be at most 72 bytes and cannot contain null characters.'));
            }
        }];
    }
}
