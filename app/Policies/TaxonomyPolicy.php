<?php

namespace App\Policies;

use App\Models\User;

class TaxonomyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canEditOthers();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, $taxonomy): bool
    {
        return $this->viewAny($user);
    }
}
