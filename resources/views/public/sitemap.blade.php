@php echo '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($posts as $post)
    <url>
        <loc>{{ route('publication.show', $post->slug) }}</loc>
        <lastmod>{{ $post->updated_at?->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>
