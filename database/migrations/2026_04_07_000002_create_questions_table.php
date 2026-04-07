<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->text('text');
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->enum('type', ['multiple_choice', 'open'])->default('multiple_choice');
            $table->text('explanation')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedSmallInteger('estimated_time_seconds')->default(30);
            $table->boolean('is_active')->default(true);
            $table->enum('source', ['manual', 'ai_generated'])->default('manual');
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('difficulty');
            $table->index('is_active');
            $table->index(['difficulty', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
