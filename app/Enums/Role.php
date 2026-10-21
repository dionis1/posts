<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Author = 'author';
    case Reviewer = 'reviewer';

    public function canEditOthers(): bool
    {
        return in_array($this, [self::Admin, self::Editor], true);
    }

    public function canReview(): bool
    {
        return in_array($this, [self::Admin, self::Editor, self::Reviewer], true);
    }
}
