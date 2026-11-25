<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('banner_image_path', 255)->nullable()->after('venue');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path', 255)->nullable()->after('phone');
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->string('photo_path', 255)->nullable()->after('email');
        });

        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('collection', 40)->default('event_gallery')->index(); // event_gallery | parish_gallery
            $table->foreignId('event_id')
                ->nullable()
                ->constrained('events')
                ->nullOnDelete();
            $table->string('title', 180)->nullable();
            $table->string('caption', 500)->nullable();
            $table->string('disk', 32)->default('public');
            $table->string('file_path', 255);
            $table->string('mime_type', 80)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('is_public')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');

        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn('photo_path');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('avatar_path');
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('banner_image_path');
        });
    }
};
