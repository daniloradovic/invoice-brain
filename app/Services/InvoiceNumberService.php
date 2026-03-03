<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    public function generate(): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($year): string {
            $count = Invoice::whereYear('created_at', $year)->lockForUpdate()->count();
            $sequential = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

            return "INV-{$year}-{$sequential}";
        });
    }
}
