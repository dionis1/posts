<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        // SQLite may rebuild the posts table when later migrations add foreign
        // keys. Rebuilds can remove the FTS triggers created in migration 000002.
        // Restore them after all posts-table schema alterations.
        DB::statement('DROP TRIGGER IF EXISTS posts_search_insert');
        DB::statement('DROP TRIGGER IF EXISTS posts_search_delete');
        DB::statement('DROP TRIGGER IF EXISTS posts_search_update');

        DB::statement("CREATE TRIGGER posts_search_insert AFTER INSERT ON posts BEGIN INSERT INTO post_search(rowid, title, body) VALUES (new.id, new.title, new.body); END");
        DB::statement("CREATE TRIGGER posts_search_delete AFTER DELETE ON posts BEGIN INSERT INTO post_search(post_search, rowid, title, body) VALUES ('delete', old.id, old.title, old.body); END");
        DB::statement("CREATE TRIGGER posts_search_update AFTER UPDATE OF title, body ON posts BEGIN INSERT INTO post_search(post_search, rowid, title, body) VALUES ('delete', old.id, old.title, old.body); INSERT INTO post_search(rowid, title, body) VALUES (new.id, new.title, new.body); END");

        // Repair the index for posts inserted while the triggers were absent.
        DB::statement("INSERT INTO post_search(post_search) VALUES ('rebuild')");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS posts_search_insert');
        DB::statement('DROP TRIGGER IF EXISTS posts_search_delete');
        DB::statement('DROP TRIGGER IF EXISTS posts_search_update');
    }
};
