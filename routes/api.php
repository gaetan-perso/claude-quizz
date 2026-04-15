<?php declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\LobbyController;
use App\Http\Controllers\Api\V1\SessionController;
use Illuminate\Support\Facades\Route;

// ── Auth (public) ─────────────────────────────────────────────────────────────
Route::prefix('v1/auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me',      [AuthController::class, 'me']);

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);

    // Leaderboard
    Route::get('leaderboard', [LeaderboardController::class, 'index']);

    // Solo sessions
    Route::get('sessions',                         [SessionController::class, 'index']);
    Route::post('sessions',                        [SessionController::class, 'store']);
    Route::get('sessions/{session}',               [SessionController::class, 'show']);
    Route::get('sessions/{session}/next-question', [SessionController::class, 'nextQuestion']);
    Route::post('sessions/{session}/answers',      [SessionController::class, 'answer']);
    Route::post('sessions/{session}/complete',     [SessionController::class, 'complete']);
    Route::post('sessions/{session}/abandon',      [SessionController::class, 'abandon']);

    // Multiplayer lobbies
    Route::post('lobbies',               [LobbyController::class, 'store']);
    Route::get('lobbies/{lobby}',        [LobbyController::class, 'show']);
    Route::post('lobbies/join',          [LobbyController::class, 'join']);
    Route::post('lobbies/{lobby}/leave', [LobbyController::class, 'leave']);
    Route::post('lobbies/{lobby}/start',    [LobbyController::class, 'start']);
    Route::post('lobbies/{lobby}/complete', [LobbyController::class, 'complete']);
});
