<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            // Rendre category_id nullable pour les sessions multi-catégories
            $table->foreignUlid('category_id')->nullable()->change();

            // Ajouter la colonne category_ids JSON après category_id
            $table->json('category_ids')->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropColumn('category_ids');
            $table->foreignUlid('category_id')->nullable(false)->change();
        });
    }
};
