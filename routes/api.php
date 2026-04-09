<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('sessions', [SessionController::class, 'store']);
    Route::get('sessions/{session}/next-question', [SessionController::class, 'nextQuestion']);
    Route::post('sessions/{session}/answers', [SessionController::class, 'answer']);
});
