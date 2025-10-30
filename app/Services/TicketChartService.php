<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class TicketChartService
{
    /**
     * Process Excel file and generate chart data
     *
     * @param mixed $file
     * @return array
     * @throws \Exception
     */
    public function processExcelFile($file): array
    {
        $sheet = Excel::toCollection(null, $file)->first(); // first worksheet

        if (!$sheet || $sheet->isEmpty()) {
            throw new \Exception('Spreadsheet is empty.');
        }

        // Detect columns using case-insensitive match
        $headers = collect($sheet->shift())->map(fn($h) => trim((string) $h));
        $rows = $sheet->values();

        $findHeader = function (array $needles) use ($headers) {
            foreach ($headers as $i => $h) {
                $hl = mb_strtolower($h);
                foreach ($needles as $n) {
                    if (str_contains($hl, mb_strtolower($n)))
                        return [$h, $i];
                }
            }
            return [null, null];
        };

        [$createdLabel, $createdIdx] = $findHeader(['ticket date created', 'date created', 'created']);
        [$resolvedLabel, $resolvedIdx] = $findHeader(['resolution date/time', 'resolution date', 'resolved', 'closed']);

        if ($createdIdx === null && $resolvedIdx === null) {
            throw new \Exception('Could not find Ticket Date Created or Resolution Date/Time columns.');
        }

        $parse = fn($v) => Carbon::parse($v, null)->startOfSecond();

        // Build daily aggregates
        $createdDaily = [];
        $resolvedDaily = [];

        foreach ($rows as $r) {
            $r = collect($r);
            if ($createdIdx !== null && ($val = $r->get($createdIdx)) !== null && $val !== '') {
                try {
                    $d = $parse($val)->toDateString();
                    $createdDaily[$d] = ($createdDaily[$d] ?? 0) + 1;
                } catch (\Throwable $e) {
                }
            }
            if ($resolvedIdx !== null && ($val = $r->get($resolvedIdx)) !== null && $val !== '') {
                try {
                    $d = $parse($val)->toDateString();
                    $resolvedDaily[$d] = ($resolvedDaily[$d] ?? 0) + 1;
                } catch (\Throwable $e) {
                }
            }
        }

        $allDays = collect(array_unique(array_merge(array_keys($createdDaily), array_keys($resolvedDaily))))
            ->sort()
            ->values();

        $categories = $allDays->all();
        $series = [
            ['name' => 'Tickets Created', 'data' => $allDays->map(fn($d) => (int) ($createdDaily[$d] ?? 0))->all()],
            ['name' => 'Tickets Resolved', 'data' => $allDays->map(fn($d) => (int) ($resolvedDaily[$d] ?? 0))->all()],
        ];

        // Optional: average resolution hours by creation day
        $avgResSeries = null;
        if ($createdIdx !== null && $resolvedIdx !== null) {
            $buckets = [];
            foreach ($rows as $r) {
                $r = collect($r);
                $c = $r->get($createdIdx);
                $res = $r->get($resolvedIdx);
                if ($c && $res) {
                    try {
                        $cd = $parse($c);
                        $rd = $parse($res);
                        $day = $cd->toDateString();
                        $hours = max(0, $rd->diffInSeconds($cd)) / 3600.0;
                        $buckets[$day][] = $hours;
                    } catch (\Throwable $e) {
                    }
                }
            }
            if ($buckets) {
                $avg = [];
                foreach ($allDays as $d) {
                    $vals = $buckets[$d] ?? [];
                    $avg[] = $vals ? array_sum($vals) / count($vals) : null;
                }
                $avgResSeries = [
                    'name' => 'Avg Resolution Hours (by creation day)',
                    'type' => 'spline',
                    'yAxis' => 1,
                    'tooltip' =>
                        ['valueDecimals' => 1],
                    'data' => $avg
                ];
            }
        }

        return [
            'categories' => $categories,
            'series' => $avgResSeries ? array_merge($series, [$avgResSeries]) : $series,
            'xTitle' => 'Date',
            'yTitle' => 'Count'
        ];
    }
}

