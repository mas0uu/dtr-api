<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DtrMonth;
use App\Models\DtrRow;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DtrController extends Controller
{
    // GET /api/dtr/months
    // Returns months dropdown + suggests current month
    public function months(Request $request)
    {
        $user = $request->user();

        $months = DtrMonth::where('user_id', $user->id)
            ->orderBy('year')
            ->orderBy('month')
            ->get(['id', 'month', 'year', 'is_fulfilled', 'created_at']);

        // If none exists yet, suggest current month
        $now = Carbon::now();
        $current = [
            'month' => (int) $now->month,
            'year' => (int) $now->year,
        ];

        return response()->json([
            'months' => $months,
            'suggested_current' => $current,
        ]);
    }

    // POST /api/dtr/months/start
    // Creates month if missing
    public function startMonth(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();

        $data = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        $month = (int) ($data['month'] ?? $now->month);
        $year  = (int) ($data['year']  ?? $now->year);

        $dtrMonth = DtrMonth::firstOrCreate(
            ['user_id' => $user->id, 'month' => $month, 'year' => $year],
            ['is_fulfilled' => false]
        );

        return response()->json([
            'message' => 'Month ready',
            'month' => $dtrMonth,
        ], 201);
    }

    // GET /api/dtr/months/{monthId}
    // Returns month + rows + whether user can add a new row
    public function monthDetail(Request $request, int $monthId)
    {
        $user = $request->user();

        $month = DtrMonth::where('user_id', $user->id)->findOrFail($monthId);

        $rows = DtrRow::where('dtr_month_id', $month->id)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $hasDraft = DtrRow::where('dtr_month_id', $month->id)
            ->where('status', 'draft')
            ->exists();

        return response()->json([
            'month' => $month,
            'rows' => $rows,
            'can_add_row' => !$hasDraft,
        ]);
    }

    // POST /api/dtr/months/{monthId}/rows
    // Creates ONE draft row, only if there is no draft yet
    public function addRow(Request $request, int $monthId)
    {
        $user = $request->user();

        $month = DtrMonth::where('user_id', $user->id)->findOrFail($monthId);

        $hasDraft = DtrRow::where('dtr_month_id', $month->id)
            ->where('status', 'draft')
            ->exists();

        if ($hasDraft) {
            return response()->json([
                'message' => 'Finish the current row first before adding a new one.',
            ], 409);
        }

        $row = DtrRow::create([
            'dtr_month_id' => $month->id,
            'status' => 'draft',
            'total_minutes' => 0,
        ]);

        return response()->json([
            'message' => 'Draft row created',
            'row' => $row,
        ], 201);
    }

    // POST /api/dtr/rows/{rowId}/finish
    // Body requires: date, time_in, time_in_meridiem, time_out, time_out_meridiem
    public function finishRow(Request $request, int $rowId)
    {
        $user = $request->user();

        $row = DtrRow::with('month')->findOrFail($rowId);

        if ((int) $row->month->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($row->status !== 'draft') {
            return response()->json(['message' => 'Row is already finished.'], 409);
        }

        $data = $request->validate([
            'date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'time_in_meridiem' => 'required|in:AM,PM',
            'time_out' => 'required|date_format:H:i',
            'time_out_meridiem' => 'required|in:AM,PM',
        ]);

        $date = Carbon::parse($data['date']);

        // Compute Day from date
        $dayName = $date->format('l');

        // Convert to real datetime for diff calculation
        $timeIn  = $this->toDateTime($date, $data['time_in'], $data['time_in_meridiem']);
        $timeOut = $this->toDateTime($date, $data['time_out'], $data['time_out_meridiem']);

        // If time-out is earlier than time-in, assume it crossed midnight (optional)
        if ($timeOut->lessThanOrEqualTo($timeIn)) {
            $timeOut = $timeOut->addDay();
        }

        $totalMinutes = $timeIn->diffInMinutes($timeOut);

        $row->update([
            'date' => $date->toDateString(),
            'day' => $dayName,
            'time_in' => $data['time_in'],
            'time_in_meridiem' => $data['time_in_meridiem'],
            'time_out' => $data['time_out'],
            'time_out_meridiem' => $data['time_out_meridiem'],
            'total_minutes' => $totalMinutes,
            'status' => 'finished',
        ]);

        return response()->json([
            'message' => 'Row finished',
            'row' => $row,
            'total_hours' => round($totalMinutes / 60, 2),
        ]);
    }

    private function toDateTime(Carbon $date, string $time24, string $meridiem): Carbon
    {
        // time24 is H:i, but user selected AM/PM separately.
        // We'll interpret the hour using meridiem:
        [$h, $m] = explode(':', $time24);
        $hour = (int) $h;

        // Convert to 12h->24h logic based on meridiem
        // If they input 12:xx, handle correctly.
        if ($meridiem === 'AM') {
            if ($hour === 12) $hour = 0;
        } else { // PM
            if ($hour !== 12) $hour += 12;
        }

        return $date->copy()->setTime($hour, (int)$m, 0);
    }
    public function currentMonth(Request $request)
    {
    $user = $request->user();

    $monthNum = now()->month;
    $year = now()->year;

    // Create if missing
    $month = DtrMonth::firstOrCreate(
        [
            'user_id' => $user->id,
            'month' => $monthNum,
            'year' => $year,
        ],
        [
            'is_fulfilled' => false,
        ]
    );

    // Load rows (ordered)
    $month->load(['rows' => function ($q) {
        $q->orderBy('id', 'asc');
    }]);

    // Compute totals for rows that have complete time-in/time-out
    $totalMinutes = 0;

    foreach ($month->rows as $row) {
        $mins = $this->computeRowMinutes($row);
        $totalMinutes += $mins;
    }

    $totalHours = round($totalMinutes / 60, 2);
    $requiredHours = (float) ($user->required_hours ?? 0);
    $remainingHours = max(0, round($requiredHours - $totalHours, 2));

    return response()->json([
        'month' => $month,
        'rows' => $month->rows,
        'summary' => [
            'total_minutes' => $totalMinutes,
            'total_hours' => $totalHours,
            'required_hours' => $requiredHours,
            'remaining_hours' => $remainingHours,
        ],
    ]);
    }

    private function computeRowMinutes($row): int
    {
        // Needs these fields to be filled to count hours
        if (
            empty($row->date) ||
            empty($row->time_in) ||
            empty($row->time_in_meridiem) ||
            empty($row->time_out) ||
            empty($row->time_out_meridiem)
        ) {
            return 0;
        }

        try {
            // Example: date = "2026-02-23", time_in="8:00", meridiem="AM"
            $in = Carbon::parse($row->date . ' ' . $row->time_in . ' ' . $row->time_in_meridiem);
            $out = Carbon::parse($row->date . ' ' . $row->time_out . ' ' . $row->time_out_meridiem);

            // If someone timed out past midnight (rare), add 1 day
            if ($out->lt($in)) {
                $out->addDay();
            }

            return max(0, $in->diffInMinutes($out));
        } catch (\Throwable $e) {
            return 0;
        }
    }
}