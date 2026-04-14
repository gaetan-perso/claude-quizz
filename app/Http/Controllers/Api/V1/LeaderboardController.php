<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuizSession;
use Illuminate\Http\JsonResponse;

final class LeaderboardController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = QuizSession::query()
            ->where('quiz_sessions.status', 'completed')
            ->join('users', 'users.id', '=', 'quiz_sessions.user_id')
            ->selectRaw('quiz_sessions.user_id, users.name, SUM(quiz_sessions.score) as total_score, COUNT(*) as sessions_count')
            ->groupBy('quiz_sessions.user_id', 'users.name')
            ->orderByDesc('total_score')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $rows->map(fn ($row, int $index) => [
                'rank'           => $index + 1,
                'user_id'        => $row->user_id,
                'name'           => $row->name,
                'total_score'    => (int) $row->total_score,
                'sessions_count' => (int) $row->sessions_count,
            ])->values(),
        ]);
    }
}
