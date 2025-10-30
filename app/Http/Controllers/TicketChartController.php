<?php

namespace App\Http\Controllers;

use App\Services\TicketChartService;
use Illuminate\Http\Request;

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
            $payload = $this->chartService->processExcelFile($data['file']);

            return view('tickets-chart', [
                'payload' => $payload
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }
    }
}