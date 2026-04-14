<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lobby_participants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lobby_id')->constrained('lobbies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(0);
            $table->boolean('is_ready')->default(false);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['lobby_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lobby_participants');
    }
};
