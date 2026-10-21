<?php

namespace App\Livewire;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EditorialStats extends Component
{
    public function render()
    {
        $counts = Post::visibleTo(Auth::user())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.editorial-stats', compact('counts'));
    }
}
