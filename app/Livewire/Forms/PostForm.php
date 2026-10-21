<?php

namespace App\Livewire\Forms;

use App\Models\Post;
use App\Support\PostContentRules;
use Livewire\Form;

class PostForm extends Form
{
    public string $title = '';

    public string $body = '';

    public string $slug = '';

    public string $excerpt = '';

    public ?int $category_id = null;

    public ?int $cover_media_id = null;

    public string $locale = 'en';

    public array $tags = [];

    public string $seo_title = '';

    public string $seo_description = '';

    public string $canonical_url = '';

    protected function validationAttributes(): array
    {
        return ['title' => __('Title'), 'body' => __('Content'), 'slug' => __('URL slug'), 'category_id' => __('Category'), 'cover_media_id' => __('Cover image')];
    }

    protected function rules(): array
    {
        return PostContentRules::for($this->getComponent()->postId);
    }

    public function loadPost(Post $post): void
    {
        foreach (['title', 'body', 'slug', 'excerpt', 'seo_title', 'seo_description', 'canonical_url'] as $field) {
            $this->$field = (string) $post->$field;
        }
        $this->category_id = $post->category_id;
        $this->cover_media_id = $post->cover_media_id;
        $this->locale = $post->locale ?? app()->getLocale();
        $this->tags = $post->tags()->pluck('tags.id')->all();
    }

    public function validatedContent(): array
    {
        $this->title = trim($this->title);
        $this->body = trim($this->body);
        $data = $this->validate();
        // Empty optional fields must remain nullable, especially the unique slug.
        foreach (['slug', 'excerpt', 'seo_title', 'seo_description', 'canonical_url'] as $key) {
            if ($data[$key] === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
