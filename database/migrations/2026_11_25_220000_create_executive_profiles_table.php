<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executive_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name', 150);
            $table->string('position', 150);
            $table->string('tenure', 80)->nullable();
            $table->string('bio', 400)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_profiles');
    }
};
