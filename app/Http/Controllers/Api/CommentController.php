<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, int $expenseId): JsonResponse
    {
        $expense = Expense::findOrFail($expenseId);

        Gate::authorize('create', [Comment::class, $expense]);

        $comment = $expense->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return response()->json([
            'data' => new CommentResource($comment->load('user')),
        ], 201);
    }

    public function index(Request $request, int $expenseId): JsonResponse
    {
        $expense = Expense::findOrFail($expenseId);

        Gate::authorize('view', $expense);

        $comments = $expense->comments()->with('user')->latest()->get();

        return response()->json([
            'data' => CommentResource::collection($comments),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);

        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
