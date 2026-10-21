<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        // Extend the existing table so an upgrade keeps legacy content and ownership.
        Schema::table('posts', function (Blueprint $table) {
            $table->renameColumn('description', 'body');
            $table->string('slug', 200)->nullable()->unique();
            $table->text('excerpt')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('cover_media_id')->nullable();
            $table->string('seo_title', 60)->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('locale', 2)->default('en');
            $table->softDeletes();
            $table->index(['status', 'scheduled_at']);
            $table->index(['status', 'published_at']);
            $table->index(['user_id', 'created_at']);
        });
        DB::table('posts')->orderBy('id')->chunkById(100, function ($posts) {
            foreach ($posts as $post) {
                DB::table('posts')->where('id', $post->id)->update([
                    'slug' => Str::limit(Str::slug($post->title) ?: 'post', 170, '').'-'.$post->id,
                ]);
            }
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });
        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version');
            $table->json('content');
            $table->timestamp('created_at')->nullable();
            $table->unique(['post_id', 'version']);
        });
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80);
            $table->json('properties');
            $table->timestamp('created_at')->nullable()->index();
            $table->index(['post_id', 'created_at']);
        });

        // FTS5 is bundled in the local Docker SQLite runtime. Triggers keep its index
        // transactionally consistent, including writes made outside Eloquent.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE VIRTUAL TABLE post_search USING fts5(title, body, content='posts', content_rowid='id')");
            DB::statement('CREATE TRIGGER posts_search_insert AFTER INSERT ON posts BEGIN INSERT INTO post_search(rowid, title, body) VALUES (new.id, new.title, new.body); END');
            DB::statement("CREATE TRIGGER posts_search_delete AFTER DELETE ON posts BEGIN INSERT INTO post_search(post_search, rowid, title, body) VALUES ('delete', old.id, old.title, old.body); END");
            DB::statement("CREATE TRIGGER posts_search_update AFTER UPDATE OF title, body ON posts BEGIN INSERT INTO post_search(post_search, rowid, title, body) VALUES ('delete', old.id, old.title, old.body); INSERT INTO post_search(rowid, title, body) VALUES (new.id, new.title, new.body); END");
            DB::statement("INSERT INTO post_search(post_search) VALUES ('rebuild')");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS posts_search_insert');
            DB::statement('DROP TRIGGER IF EXISTS posts_search_delete');
            DB::statement('DROP TRIGGER IF EXISTS posts_search_update');
            DB::statement('DROP TABLE IF EXISTS post_search');
        }
        Schema::dropIfExists('activities');
        Schema::dropIfExists('post_revisions');
        Schema::dropIfExists('post_tag');
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['status', 'scheduled_at']);
            $table->dropIndex(['status', 'published_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'excerpt', 'status', 'lock_version', 'published_at', 'scheduled_at', 'category_id', 'cover_media_id', 'seo_title', 'seo_description', 'canonical_url', 'locale', 'deleted_at']);
            $table->renameColumn('body', 'description');
        });
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};
