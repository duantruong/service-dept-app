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

            return view('tickets-chart', [
                'currentWeek' => $combinedWeek,
                'weekIndex' => 0,
                'totalWeeks' => 1,
                'hasPrevious' => false,
                'hasNext' => false,
                'dateFilter' => $dateFilter,
                'isFiltered' => true,
                'allWeeks' => [], // No weeks list for filtered view
            ]);
        } else {
            // Normal pagination for unfiltered data
            $weekIndex = max(0, min((int) $week, $totalWeeks - 1));
            $currentWeek = $weeklyData['weeks'][$weekIndex];

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
            ]);
        }
    }
}