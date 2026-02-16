<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add pinned, tags, color to notes
        Schema::table('notes_notes', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('done_at');
            $table->json('tags')->nullable()->after('is_pinned');
            $table->string('color', 20)->nullable()->after('tags');
        });

        // Add pinned, tags, color to folders
        Schema::table('notes_folders', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('done_at');
            $table->json('tags')->nullable()->after('is_pinned');
            $table->string('color', 20)->nullable()->after('tags');
        });

        // Create shared notes table
        Schema::create('notes_note_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes_notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission', 20)->default('view'); // view, edit
            $table->timestamps();
            $table->unique(['note_id', 'user_id']);
        });

        // Add fulltext index for search
        Schema::table('notes_notes', function (Blueprint $table) {
            $table->fullText(['name', 'content'], 'notes_notes_fulltext');
        });
    }

    public function down(): void
    {
        Schema::table('notes_notes', function (Blueprint $table) {
            $table->dropFullText('notes_notes_fulltext');
            $table->dropColumn(['is_pinned', 'tags', 'color']);
        });

        Schema::table('notes_folders', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'tags', 'color']);
        });

        Schema::dropIfExists('notes_note_shares');
    }
};
