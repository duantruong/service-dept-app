<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class TicketChartService
{
    /**
     * Process Excel file and generate weekly chart data with pagination
     *
     * @param mixed $file
     * @param string|null $startDate Optional start date filter (Y-m-d format)
     * @param string|null $endDate Optional end date filter (Y-m-d format)
     * @return array
     * @throws \Exception
     */
    public function processExcelFile($file, $startDate = null, $endDate = null): array
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
        [$smcTicketLabel, $smcTicketIdx] = $findHeader(['smc ticket', 'ticket', 'ticket number', 'ticket id']);

        if ($createdIdx === null) {
            throw new \Exception('Could not find Ticket Date Created column.');
        }

        // Improved date parsing function
        $parse = function ($v) {
            if (empty($v)) {
                return null;
            }

            // Try PhpSpreadsheet date parsing first (for Excel date numbers)
            if (is_numeric($v)) {
                try {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v);
                } catch (\Throwable $e) {
                    // If not an Excel date number, continue with other methods
                }
            }

            // Try Carbon parsing
            try {
                $date = Carbon::parse($v);
                // Check if date is reasonable (not 1970 which indicates parsing failure)
                if ($date->year >= 2000 && $date->year <= 2100) {
                    return $date;
                }
            } catch (\Throwable $e) {
            }

            return null;
        };

        // Helper to check if date is within range
        $isInDateRange = function ($date) use ($startDate, $endDate) {
            if ($startDate && $date->lt(Carbon::parse($startDate)->startOfDay())) {
                return false;
            }
            if ($endDate && $date->gt(Carbon::parse($endDate)->endOfDay())) {
                return false;
            }
            return true;
        };

        // Helper to get week start date (Monday of the week)
        $getWeekStart = fn($date) => $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString();

        // Build daily aggregates (for daily view) and weekly aggregates
        $createdDaily = [];
        $resolvedDaily = [];
        $createdWeekly = [];
        $resolvedWeekly = [];
        
        // Track individual tickets with their SMC Ticket numbers
        $ticketsByDate = []; // ['2025-01-01' => ['created' => ['SMC001', 'SMC002'], 'resolved' => ['SMC001']]]
        $allTickets = []; // Store all ticket data for lookup

        foreach ($rows as $r) {
            $r = collect($r);
            $smcTicket = $smcTicketIdx !== null ? trim((string) ($r->get($smcTicketIdx) ?? '')) : null;

            // Count tickets created by day and week (only 2025)
            if ($createdIdx !== null && ($val = $r->get($createdIdx)) !== null && $val !== '') {
                try {
                    $dateObj = $parse($val);
                    if ($dateObj) {
                        $date = Carbon::instance($dateObj)->startOfDay();
                        // Only include dates from 2025 and within date range
                        if ($date->year === 2025 && $isInDateRange($date)) {
                            $dayKey = $date->toDateString();
                            $weekStart = $getWeekStart($date);

                            $createdDaily[$dayKey] = ($createdDaily[$dayKey] ?? 0) + 1;
                            $createdWeekly[$weekStart] = ($createdWeekly[$weekStart] ?? 0) + 1;
                            
                            // Track ticket number
                            if ($smcTicket) {
                                if (!isset($ticketsByDate[$dayKey])) {
                                    $ticketsByDate[$dayKey] = ['created' => [], 'resolved' => []];
                                }
                                $ticketsByDate[$dayKey]['created'][] = $smcTicket;
                                
                                // Store ticket info
                                if (!isset($allTickets[$smcTicket])) {
                                    $allTickets[$smcTicket] = [
                                        'smc_ticket' => $smcTicket,
                                        'created_date' => $dayKey,
                                        'resolved_date' => null,
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            // Count tickets resolved by day and week (only 2025)
            if ($resolvedIdx !== null && ($val = $r->get($resolvedIdx)) !== null && $val !== '') {
                try {
                    $dateObj = $parse($val);
                    if ($dateObj) {
                        $date = Carbon::instance($dateObj)->startOfDay();
                        // Only include dates from 2025 and within date range
                        if ($date->year === 2025 && $isInDateRange($date)) {
                            $dayKey = $date->toDateString();
                            $weekStart = $getWeekStart($date);

                            $resolvedDaily[$dayKey] = ($resolvedDaily[$dayKey] ?? 0) + 1;
                            $resolvedWeekly[$weekStart] = ($resolvedWeekly[$weekStart] ?? 0) + 1;
                            
                            // Track ticket number
                            if ($smcTicket) {
                                if (!isset($ticketsByDate[$dayKey])) {
                                    $ticketsByDate[$dayKey] = ['created' => [], 'resolved' => []];
                                }
                                $ticketsByDate[$dayKey]['resolved'][] = $smcTicket;
                                
                                // Update ticket info
                                if (isset($allTickets[$smcTicket])) {
                                    $allTickets[$smcTicket]['resolved_date'] = $dayKey;
                                } else {
                                    $allTickets[$smcTicket] = [
                                        'smc_ticket' => $smcTicket,
                                        'created_date' => null,
                                        'resolved_date' => $dayKey,
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        // Get all unique weeks and sort them
        $allWeeks = collect(array_unique(array_merge(array_keys($createdWeekly), array_keys($resolvedWeekly))))
            ->map(fn($d) => Carbon::parse($d))
            ->sort()
            ->map(fn($d) => $d->toDateString())
            ->values();

        // If no weeks found, return empty structure
        if ($allWeeks->isEmpty()) {
            return [
                'weeks' => [],
                'totalWeeks' => 0
            ];
        }

        // Build weekly data with ticket counts and daily breakdown
        $weeklyData = [];
        $runningTotal = 0;

        foreach ($allWeeks as $weekStart) {
            $created = (int) ($createdWeekly[$weekStart] ?? 0);
            $resolved = (int) ($resolvedWeekly[$weekStart] ?? 0);

            $runningTotal += $created - $resolved;

            $start = Carbon::parse($weekStart);
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);

            // Build daily data for this week (Monday to Sunday)
            $dailyData = [];
            $dayRunningTotal = $runningTotal - $created + $resolved; // Start with total before this week

            for ($day = 0; $day < 7; $day++) {
                $currentDay = $start->copy()->addDays($day);
                $dayKey = $currentDay->toDateString();
                $dayCreated = (int) ($createdDaily[$dayKey] ?? 0);
                $dayResolved = (int) ($resolvedDaily[$dayKey] ?? 0);

                $dayRunningTotal += $dayCreated - $dayResolved;

                $dailyData[] = [
                    'day' => $currentDay->format('D'), // Mon, Tue, Wed, etc.
                    'dayFull' => $currentDay->format('l'), // Monday, Tuesday, etc.
                    'date' => $currentDay->format('M j'), // Jan 1
                    'dateFull' => $dayKey,
                    'created' => $dayCreated,
                    'resolved' => $dayResolved,
                    'totalTickets' => $dayRunningTotal,
                ];
            }

            $weeklyData[] = [
                'weekStart' => $weekStart,
                'weekLabel' => $start->format('M j') . ' - ' . $end->format('M j, Y'),
                'created' => $created,
                'resolved' => $resolved,
                'totalTickets' => $runningTotal,
                'weekStartFormatted' => $start->format('Y-m-d'),
                'weekEndFormatted' => $end->format('Y-m-d'),
                'dailyData' => $dailyData,
            ];
        }

        return [
            'weeks' => $weeklyData,
            'totalWeeks' => count($weeklyData),
            'ticketsByDate' => $ticketsByDate,
            'allTickets' => $allTickets,
        ];
    }
}

