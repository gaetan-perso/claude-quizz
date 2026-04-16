<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('max_questions')->default(20)->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table): void {
            $table->dropColumn('max_questions');
        });
    }
};
