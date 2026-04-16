<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lobbies', function (Blueprint $table) {
            $table->json('category_ids')->nullable()->after('category_id');
            $table->unsignedSmallInteger('max_questions')->default(10)->after('max_players');
        });
    }

    public function down(): void
    {
        Schema::table('lobbies', function (Blueprint $table) {
            $table->dropColumn(['category_ids', 'max_questions']);
        });
    }
};
