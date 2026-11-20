<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FinancialReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        [$period, $from, $to] = $this->range($request, $request->string('period')->toString());

        return Inertia::render('Admin/Reports', [
            'filters' => [
                'period' => $period,
                'from' => $from?->copy()->timezone('Asia/Muscat')->toDateString() ?? '',
                'to' => $to?->copy()->timezone('Asia/Muscat')->toDateString() ?? '',
            ],
            'report' => FinancialReport::make($from, $to),
        ]);
    }

    /**
     * @return array{0: string, 1: ?Carbon, 2: ?Carbon}
     */
    private function range(Request $request, string $period): array
    {
        $today = Carbon::now('Asia/Muscat')->startOfDay();

        if ($period === 'custom') {
            $start = $this->muscatDate($request->string('from')->toString());
            $end = $this->muscatDate($request->string('to')->toString());

            if ($start && $end) {
                if ($start->greaterThan($end)) {
                    [$start, $end] = [$end, $start];
                }

                return ['custom', $start->copy()->startOfDay()->utc(), $end->copy()->endOfDay()->utc()];
            }

            $period = 'month';
        }

        if ($period === 'all') {
            return ['all', null, null];
        }

        $start = match ($period) {
            'today' => $today->copy(),
            'week' => $today->copy()->subDays(6),
            'year' => $today->copy()->startOfYear(),
            'month' => $today->copy()->startOfMonth(),
            default => null,
        };

        if ($start === null) {
            $period = 'month';
            $start = $today->copy()->startOfMonth();
        }

        return [$period, $start->startOfDay()->utc(), $today->copy()->endOfDay()->utc()];
    }

    private function muscatDate(string $value): ?Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, 'Asia/Muscat');
        } catch (\Throwable) {
            return null;
        }

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date->startOfDay();
    }
}
