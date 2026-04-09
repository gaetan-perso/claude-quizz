<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->enum('current_difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->unsignedTinyInteger('consecutive_correct')->default(0);
            $table->unsignedTinyInteger('consecutive_wrong')->default(0);
            $table->unsignedSmallInteger('score')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_sessions');
    }
};
