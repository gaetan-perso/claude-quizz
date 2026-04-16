<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table): void {
            $table->ulid('lobby_id')->nullable()->after('user_id');

            $table->foreign('lobby_id')
                ->references('id')
                ->on('lobbies')
                ->nullOnDelete();

            $table->index('lobby_id');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table): void {
            $table->dropForeign(['lobby_id']);
            $table->dropIndex(['lobby_id']);
            $table->dropColumn('lobby_id');
        });
    }
};
