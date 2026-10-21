<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });
        Schema::table('posts', function (Blueprint $table) {
            $table->foreign('cover_media_id')->references('id')->on('media')->nullOnDelete();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->unsignedTinyInteger('depth')->default(0);
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->index(['post_id', 'status', 'created_at']);
        });
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->char('visitor_hash', 64);
            $table->unique(['post_id', 'date', 'visitor_hash']);
            $table->index('date');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('post_views');
        Schema::dropIfExists('comments');
        Schema::table('posts', fn (Blueprint $table) => $table->dropForeign(['cover_media_id']));
        Schema::dropIfExists('media');
    }
};
