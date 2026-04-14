<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lobbies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('host_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->enum('status', ['waiting', 'in_progress', 'completed'])->default('waiting');
            $table->string('code', 6)->unique();
            $table->unsignedTinyInteger('max_players')->default(4);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lobbies');
    }
};
