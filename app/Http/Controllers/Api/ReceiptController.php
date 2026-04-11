<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Receipt\StoreReceiptRequest;
use App\Http\Resources\ReceiptResource;
use App\Models\Expense;
use App\Models\Receipt;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptController extends Controller
{
    public function __construct(
        private ReceiptService $receiptService
    ) {}

    public function store(StoreReceiptRequest $request, int $expenseId): JsonResponse
    {
        $expense = Expense::findOrFail($expenseId);

        Gate::authorize('upload', [Receipt::class, $expense]);

        $receipt = $this->receiptService->upload($expense, $request->file('file'));

        return response()->json([
            'data' => new ReceiptResource($receipt),
        ], 201);
    }

    public function index(Request $request, int $expenseId): JsonResponse
    {
        $expense = Expense::findOrFail($expenseId);

        Gate::authorize('view', $expense);

        $receipts = $expense->receipts;

        return response()->json([
            'data' => ReceiptResource::collection($receipts),
        ]);
    }

    public function download(Request $request, int $id): StreamedResponse
    {
        $receipt = Receipt::with('expense')->findOrFail($id);

        Gate::authorize('download', $receipt);

        return Storage::disk('local')->download(
            $receipt->file_path,
            $receipt->original_name
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $receipt = Receipt::with('expense')->findOrFail($id);

        Gate::authorize('delete', $receipt);

        $this->receiptService->delete($receipt);

        return response()->json(['message' => 'Receipt deleted.']);
    }
}
