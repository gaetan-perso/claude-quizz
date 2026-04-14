<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

final class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::active()->orderBy('name')->get();

        return response()->json([
            'data' => $categories->map(fn (Category $c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'slug'  => $c->slug,
                'icon'  => $c->icon,
                'color' => $c->color,
            ])->values(),
        ]);
    }
}
