<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseService $expenseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $expenses = $this->expenseService->list(
            $request->user(),
            $request->query()
        );

        return response()->json([
            'data' => ExpenseResource::collection($expenses),
            'meta' => [
                'next_cursor' => $expenses->nextCursor()?->encode(),
                'prev_cursor' => $expenses->previousCursor()?->encode(),
                'per_page' => $expenses->perPage(),
            ],
        ]);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->expenseService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'data' => new ExpenseResource($expense->load(['user', 'team', 'category'])),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $expense = $this->expenseService->find($request->user(), $id);

        Gate::authorize('view', $expense);

        return response()->json([
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function update(UpdateExpenseRequest $request, int $id): JsonResponse
    {
        $expense = $this->expenseService->find($request->user(), $id);

        Gate::authorize('update', $expense);

        $expense = $this->expenseService->update($expense, $request->validated());

        return response()->json([
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $expense = $this->expenseService->find($request->user(), $id);

        Gate::authorize('delete', $expense);

        $this->expenseService->delete($expense);

        return response()->json(['message' => 'Expense deleted.']);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $expense = $this->expenseService->find($request->user(), $id);

        Gate::authorize('submit', $expense);

        $expense = $this->expenseService->submit($expense);

        return response()->json([
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $expense = $this->expenseService->find($request->user(), $id);

        Gate::authorize('approve', $expense);

        $expense = $this->expenseService->approve($request->user(), $expense);

        return response()->json([
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $expense = $this->expenseService->find($request->user(), $id);

        Gate::authorize('reject', $expense);

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $expense = $this->expenseService->reject(
            $request->user(),
            $expense,
            $request->input('rejection_reason')
        );

        return response()->json([
            'data' => new ExpenseResource($expense),
        ]);
    }
}