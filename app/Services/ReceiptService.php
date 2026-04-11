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
    $disk = config('app.env') === 'production' ? 'r2' : 'local';
    $path = $file->store("receipts/{$expense->id}", $disk);

    return Receipt::create([
        'expense_id' => $expense->id,
        'file_path' => $path,
        'disk' => $disk,
        'original_name' => $file->getClientOriginalName(),
        'mime_type' => $file->getMimeType(),
        'size' => $file->getSize(),
    ]);
}

public function delete(Receipt $receipt): void
{
    $disk = $receipt->disk ?? 'local';
    Storage::disk($disk)->delete($receipt->file_path);
    $receipt->delete();
}
}