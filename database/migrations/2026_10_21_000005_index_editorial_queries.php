<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'posts' => [['user_id', 'updated_at', 'id'], ['status', 'updated_at', 'id'], ['category_id', 'updated_at', 'id'], ['cover_media_id']],
        'media' => [['user_id', 'created_at']],
        'comments' => [['parent_id'], ['user_id'], ['moderated_by']],
        'post_revisions' => [['user_id']],
        'activities' => [['actor_id']],
        'post_tag' => [['tag_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $columns) {
                    $blueprint->index($columns);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                foreach ($indexes as $columns) {
                    $blueprint->dropIndex($columns);
                }
            });
        }
    }
};
