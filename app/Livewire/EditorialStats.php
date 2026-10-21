<?php

namespace App\Livewire;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EditorialStats extends Component
{
    #[Computed]
    public function counts()
    {
        abort_unless(Auth::user()?->hasVerifiedEmail(), 403);

        return Post::visibleTo(Auth::user())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

    }

    public function render()
    {
        return view('livewire.editorial-stats', ['counts' => $this->counts]);
    }
}
