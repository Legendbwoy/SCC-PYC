<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('sequence_type', 40);
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['sequence_type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_sequences');
    }
};
