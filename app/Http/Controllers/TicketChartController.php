<?php

namespace App\Http\Controllers;

use App\Services\TicketChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class TicketChartController extends Controller
{
    protected TicketChartService $chartService;

    public function __construct(TicketChartService $chartService)
    {
        $this->chartService = $chartService;
    }

    public function form()
    {
        return view('tickets-form');
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        try {
            // Store file temporarily for filtering
            $filePath = $data['file']->store('temp');
            session(['ticket_file_path' => $filePath]);

            // Process with default filters (all 2025 data)
            $weeklyData = $this->chartService->processExcelFile($data['file']);

            // Store weekly data in session for pagination
            session(['ticket_weekly_data' => $weeklyData]);
            session(['ticket_date_filter' => null]); // Clear any previous filters

            // Redirect to first week (index 0)
            return redirect()->route('tickets.chart', ['week' => 0]);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }

    public function filter(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $filePath = session('ticket_file_path');
        if (!$filePath || !Storage::exists($filePath)) {
            return redirect()->route('home')->withErrors(['error' => 'File not found. Please upload again.']);
        }

        try {
            $fullPath = Storage::path($filePath);

            // Create UploadedFile instance from stored file
            $uploadedFile = new UploadedFile(
                $fullPath,
                basename($filePath),
                Storage::mimeType($filePath),
                null,
                true
            );

            // Process with date filters
            $weeklyData = $this->chartService->processExcelFile(
                $uploadedFile,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null
            );

            // Store filtered data in session
            session(['ticket_weekly_data' => $weeklyData]);
            session([
                'ticket_date_filter' => [
                    'start_date' => $data['start_date'] ?? null,
                    'end_date' => $data['end_date'] ?? null,
                ]
            ]);

            // Redirect to first week
            return redirect()->route('tickets.chart', ['week' => 0]);
        } catch (\Exception $e) {
            return redirect()->route('tickets.chart', ['week' => $request->input('current_week', 0)])
                ->withErrors(['filter' => $e->getMessage()]);
        }
    }

    public function showChart(Request $request, $week = 0)
    {
        // Handle clear filter request
        if ($request->has('clear_filter')) {
            $filePath = session('ticket_file_path');
            if ($filePath && Storage::exists($filePath)) {
                try {
                    $fullPath = Storage::path($filePath);

                    $uploadedFile = new UploadedFile(
                        $fullPath,
                        basename($filePath),
                        Storage::mimeType($filePath),
                        null,
                        true
                    );

                    // Reprocess without filters
                    $weeklyData = $this->chartService->processExcelFile($uploadedFile);
                    session(['ticket_weekly_data' => $weeklyData]);
                    session(['ticket_date_filter' => null]);
                } catch (\Exception $e) {
                    // Continue with existing data if reprocessing fails
                }
            }
            return redirect()->route('tickets.chart', ['week' => $week]);
        }

        $weeklyData = session('ticket_weekly_data');

        if (!$weeklyData || empty($weeklyData['weeks'])) {
            return redirect()->route('home')->withErrors(['error' => 'No chart data found. Please upload a file first.']);
        }

        $totalWeeks = $weeklyData['totalWeeks'];
        $dateFilterSession = session('ticket_date_filter');
        $dateFilter = is_array($dateFilterSession) ? $dateFilterSession : ['start_date' => null, 'end_date' => null];

        // Check if filter is active
        $isFiltered = ($dateFilter['start_date'] ?? null) || ($dateFilter['end_date'] ?? null);

        if ($isFiltered && $totalWeeks > 0) {
            // Combine all filtered weeks into one dataset
            $allDailyData = [];
            $totalCreated = 0;
            $totalResolved = 0;
            $runningTotal = 0;
            $ticketsByDate = $weeklyData['ticketsByDate'] ?? [];
            $allTickets = $weeklyData['allTickets'] ?? [];

            // Flatten all daily data while maintaining chronological order
            // Calculate running total across all days sequentially
            foreach ($weeklyData['weeks'] as $week) {
                $totalCreated += $week['created'];
                $totalResolved += $week['resolved'];

                foreach ($week['dailyData'] as $day) {
                    // Calculate running total correctly: add created, subtract resolved
                    $runningTotal += $day['created'] - $day['resolved'];
                    $allDailyData[] = [
                        'day' => $day['day'],
                        'dayFull' => $day['dayFull'] ?? $day['day'],
                        'date' => $day['date'],
                        'dateFull' => $day['dateFull'],
                        'created' => $day['created'],
                        'resolved' => $day['resolved'],
                        'totalTickets' => $runningTotal,
                    ];
                }
            }

            // Create combined week data
            $startDate = $dateFilter['start_date'] ? \Carbon\Carbon::parse($dateFilter['start_date'])->format('M j, Y') : 'Beginning';
            $endDate = $dateFilter['end_date'] ? \Carbon\Carbon::parse($dateFilter['end_date'])->format('M j, Y') : 'End';

            $combinedWeek = [
                'weekLabel' => $startDate . ' - ' . $endDate,
                'created' => $totalCreated,
                'resolved' => $totalResolved,
                'totalTickets' => $runningTotal,
                'dailyData' => $allDailyData,
            ];

            // Calculate filtered tickets for display
            $filteredTickets = [
                'created' => [],
                'resolved' => [],
                'remaining' => [],
            ];

            // Collect all created and resolved tickets in filtered range
            foreach ($ticketsByDate as $dateTickets) {
                $filteredTickets['created'] = array_merge($filteredTickets['created'], $dateTickets['created'] ?? []);
                $filteredTickets['resolved'] = array_merge($filteredTickets['resolved'], $dateTickets['resolved'] ?? []);
            }

            // Calculate remaining tickets
            $filterStart = $dateFilter['start_date'] ? \Carbon\Carbon::parse($dateFilter['start_date']) : null;
            $filterEnd = $dateFilter['end_date'] ? \Carbon\Carbon::parse($dateFilter['end_date'])->endOfDay() : null;

            foreach ($allTickets as $ticket) {
                $createdDate = $ticket['created_date'] ? \Carbon\Carbon::parse($ticket['created_date']) : null;
                $resolvedDate = $ticket['resolved_date'] ? \Carbon\Carbon::parse($ticket['resolved_date']) : null;

                if ($createdDate) {
                    $inRange = true;
                    if ($filterStart && $createdDate->lt($filterStart->startOfDay())) {
                        $inRange = false;
                    }
                    if ($filterEnd && $createdDate->gt($filterEnd)) {
                        $inRange = false;
                    }

                    if ($inRange && (!$resolvedDate || ($filterEnd && $resolvedDate->gt($filterEnd)))) {
                        $filteredTickets['remaining'][] = $ticket['smc_ticket'];
                    }
                }
            }

            // Remove duplicates
            $filteredTickets['created'] = array_unique($filteredTickets['created']);
            $filteredTickets['resolved'] = array_unique($filteredTickets['resolved']);
            $filteredTickets['remaining'] = array_unique($filteredTickets['remaining']);

            return view('tickets-chart', [
                'currentWeek' => $combinedWeek,
                'weekIndex' => 0,
                'totalWeeks' => 1,
                'hasPrevious' => false,
                'hasNext' => false,
                'dateFilter' => $dateFilter,
                'isFiltered' => true,
                'allWeeks' => [], // No weeks list for filtered view
                'weekTickets' => $filteredTickets,
            ]);
        } else {
            // Normal pagination for unfiltered data
            $weekIndex = max(0, min((int) $week, $totalWeeks - 1));
            $currentWeek = $weeklyData['weeks'][$weekIndex];

            // Get tickets for this week
            $weekStart = $currentWeek['weekStartFormatted'];
            $weekEnd = $currentWeek['weekEndFormatted'];
            $ticketsByDate = $weeklyData['ticketsByDate'] ?? [];
            $allTickets = $weeklyData['allTickets'] ?? [];

            // Collect tickets for this week
            $weekTickets = [
                'created' => [],
                'resolved' => [],
                'remaining' => [],
            ];

            $startDate = \Carbon\Carbon::parse($weekStart);
            $endDate = \Carbon\Carbon::parse($weekEnd);

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dayKey = $date->toDateString();
                if (isset($ticketsByDate[$dayKey])) {
                    $weekTickets['created'] = array_merge($weekTickets['created'], $ticketsByDate[$dayKey]['created'] ?? []);
                    $weekTickets['resolved'] = array_merge($weekTickets['resolved'], $ticketsByDate[$dayKey]['resolved'] ?? []);
                }
            }

            // Get remaining tickets (created but not resolved in this week, or created before and still open)
            foreach ($allTickets as $ticket) {
                $createdDate = $ticket['created_date'] ? \Carbon\Carbon::parse($ticket['created_date']) : null;
                $resolvedDate = $ticket['resolved_date'] ? \Carbon\Carbon::parse($ticket['resolved_date']) : null;

                // Ticket is remaining if:
                // 1. Created before or during this week AND (not resolved OR resolved after this week)
                if ($createdDate && $createdDate->lte($endDate)) {
                    if (!$resolvedDate || $resolvedDate->gt($endDate)) {
                        $weekTickets['remaining'][] = $ticket['smc_ticket'];
                    }
                }
            }

            // Remove duplicates
            $weekTickets['created'] = array_unique($weekTickets['created']);
            $weekTickets['resolved'] = array_unique($weekTickets['resolved']);
            $weekTickets['remaining'] = array_unique($weekTickets['remaining']);

            // Get all weeks for dropdown
            $allWeeks = [];
            foreach ($weeklyData['weeks'] as $index => $week) {
                $allWeeks[] = [
                    'index' => $index,
                    'label' => $week['weekLabel'],
                    'weekNumber' => $index + 1,
                ];
            }

            return view('tickets-chart', [
                'currentWeek' => $currentWeek,
                'weekIndex' => $weekIndex,
                'totalWeeks' => $totalWeeks,
                'hasPrevious' => $weekIndex > 0,
                'hasNext' => $weekIndex < $totalWeeks - 1,
                'dateFilter' => $dateFilter,
                'isFiltered' => false,
                'allWeeks' => $allWeeks,
                'weekTickets' => $weekTickets,
            ]);
        }
    }

    public function getTickets(Request $request)
    {
        $type = $request->input('type'); // 'created', 'resolved', or 'remaining'
        $weekIndex = (int) $request->input('week', 0);
        $isFiltered = $request->input('filtered', false);

        $weeklyData = session('ticket_weekly_data');
        if (!$weeklyData) {
            return response()->json(['tickets' => []]);
        }

        $tickets = [];

        if ($isFiltered) {
            // For filtered view, get tickets from all dates
            $allTickets = $weeklyData['allTickets'] ?? [];
            $ticketsByDate = $weeklyData['ticketsByDate'] ?? [];

            if ($type === 'created') {
                foreach ($ticketsByDate as $dateTickets) {
                    $tickets = array_merge($tickets, $dateTickets['created'] ?? []);
                }
            } elseif ($type === 'resolved') {
                foreach ($ticketsByDate as $dateTickets) {
                    $tickets = array_merge($tickets, $dateTickets['resolved'] ?? []);
                }
            } elseif ($type === 'remaining') {
                // Remaining = created but not resolved within the filtered range
                // For filtered view, we need to check tickets created in range that weren't resolved
                $dateFilter = session('ticket_date_filter', ['start_date' => null, 'end_date' => null]);
                $filterStart = $dateFilter['start_date'] ? \Carbon\Carbon::parse($dateFilter['start_date']) : null;
                $filterEnd = $dateFilter['end_date'] ? \Carbon\Carbon::parse($dateFilter['end_date'])->endOfDay() : null;

                foreach ($allTickets as $ticket) {
                    $createdDate = $ticket['created_date'] ? \Carbon\Carbon::parse($ticket['created_date']) : null;
                    $resolvedDate = $ticket['resolved_date'] ? \Carbon\Carbon::parse($ticket['resolved_date']) : null;

                    // Ticket is remaining if created in range and (not resolved OR resolved after range end)
                    if ($createdDate) {
                        $inRange = true;
                        if ($filterStart && $createdDate->lt($filterStart->startOfDay())) {
                            $inRange = false;
                        }
                        if ($filterEnd && $createdDate->gt($filterEnd)) {
                            $inRange = false;
                        }

                        if ($inRange && (!$resolvedDate || ($filterEnd && $resolvedDate->gt($filterEnd)))) {
                            $tickets[] = $ticket['smc_ticket'];
                        }
                    }
                }
            }
        } else {
            // For weekly view, use the pre-calculated week tickets
            $weekTicketsParam = $request->input('weekTickets');
            if (is_string($weekTicketsParam)) {
                $weekTickets = json_decode($weekTicketsParam, true) ?? [];
            } else {
                $weekTickets = $weekTicketsParam ?? [];
            }
            $tickets = $weekTickets[$type] ?? [];
        }

        // Remove duplicates and sort
        $tickets = array_values(array_unique($tickets));
        sort($tickets);

        return response()->json(['tickets' => $tickets]);
    }
}