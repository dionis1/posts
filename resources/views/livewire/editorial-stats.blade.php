<div class="stats-grid">
    @foreach ([
        'draft' => ['Drafts', 'In the making'],
        'review' => ['In review', 'Ready for another perspective'],
        'scheduled' => ['Scheduled', 'On the calendar'],
        'published' => ['Published', 'Out in the world'],
    ] as $state => $labels)
        <a class="stat-card" href="{{ route('posts.index', ['status' => $state]) }}">
            <span class="stat-label">{{ __($labels[0]) }}</span>
            <strong>{{ $counts[$state] ?? 0 }}</strong>
            <span class="muted">{{ __($labels[1]) }}</span>
        </a>
    @endforeach
</div>
