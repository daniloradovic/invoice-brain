<?php

namespace App\Http\Controllers;

use App\Enums\WorkLogStatus;
use App\Models\WorkLog;
use Illuminate\View\View;

class WorkLogController extends Controller
{
    public function index(): View
    {
        $workLogs = WorkLog::with('client')
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [WorkLogStatus::Unbilled->value])
            ->orderByDesc('worked_at')
            ->get()
            ->groupBy('client_id');

        return view('worklogs.index', compact('workLogs'));
    }
}
