<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Receipt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    public function upload(Expense $expense, UploadedFile $file): Receipt
    {
        $path = $file->store("receipts/{$expense->id}", 'local');

        return Receipt::create([
            'expense_id' => $expense->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function delete(Receipt $receipt): void
    {
        Storage::disk('local')->delete($receipt->file_path);
        $receipt->delete();
    }
}