<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::orderBy('name');

        // Non-admins only see active categories
        if ($request->user()->role !== UserRole::Admin) {
            $query->where('is_active', true);
        }

        return response()->json([
            'data' => CategoryResource::collection($query->get()),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json([
            'data' => new CategoryResource($category),
        ], 201);
    }
}