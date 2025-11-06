@extends('layouts.default')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/chart.css') }}">
@endpush

@section('maincontent')
    <div class="d-flex flex-column align-items-center position-relative">
        <!-- Date Filter Box -->
        <div class="card shadow p-4 mb-4 filter-card">
            <div class="card-header bg-transparent border-0 pb-2">
                <h4 class="mb-0 filter-header">
                    <i class="bi bi-funnel"></i> Date Range Filter
                </h4>
            </div>
            <div class="card-body">
                @if ($errors->has('filter'))
                    <div class="alert alert-danger mb-3">
                        {{ $errors->first('filter') }}
                    </div>
                @endif
                <form action="{{ route('tickets.filter') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <input type="hidden" name="current_week" value="{{ $weekIndex }}">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label filter-label">
                            <strong>From Date:</strong>
                        </label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date"
                            name="start_date" value="{{ $dateFilter['start_date'] ?? '' }}" max="{{ date('Y-m-d') }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label filter-label">
                            <strong>To Date:</strong>
                        </label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date"
                            name="end_date" value="{{ $dateFilter['end_date'] ?? '' }}"
                            min="{{ $dateFilter['start_date'] ?? '2025-01-01' }}" max="{{ date('Y-m-d') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            Apply Filter
                        </button>
                        @if(
                                isset($dateFilter) && (($dateFilter['start_date'] ?? null) || ($dateFilter['end_date'] ??
                                    null))
                            )
                            <a href="{{ route('tickets.chart', ['week' => $weekIndex]) }}?clear_filter=1"
                                class="btn btn-outline-secondary w-100 mt-2">
                                Clear Filter
                            </a>
                        @endif
                    </div>
                </form>
                @if(isset($dateFilter) && (($dateFilter['start_date'] ?? null) || ($dateFilter['end_date'] ?? null)))
                    <div class="filtered-info">
                        <small class="text-muted">
                            <strong>Currently Filtered:</strong>
                            {{ ($dateFilter['start_date'] ?? null) ? date('M j, Y', strtotime($dateFilter['start_date'])) : 'Beginning' }}
                            to
                            {{ ($dateFilter['end_date'] ?? null) ? date('M j, Y', strtotime($dateFilter['end_date'])) : 'End' }}
                        </small>
                    </div>
                @endif
            </div>
        </div>

        <!-- Main Chart Card -->
        <div class="card shadow p-4 chart-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                    ← Back to Home
                </a>
                <h3 class="text-center mb-0 chart-header">
                    @if(isset($isFiltered) && $isFiltered)
                        Tickets Chart - Filtered Results
                    @else
                        Tickets Chart - Week {{ $weekIndex + 1 }} of {{ $totalWeeks }}
                    @endif
                </h3>
                <div class="chart-spacer"></div>
            </div>

            <h4 class="text-center mb-4 week-label">{{ $currentWeek['weekLabel'] }}</h4>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white clickable-ticket-box" data-type="created"
                        style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5>Tickets Created</h5>
                            <h2>{{ $currentWeek['created'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white clickable-ticket-box" data-type="resolved"
                        style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5>Tickets Resolved</h5>
                            <h2>{{ $currentWeek['resolved'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white clickable-ticket-box" data-type="remaining"
                        style="cursor: pointer;">
                        <div class="card-body text-center">
                            <h5>Remaining Tickets</h5>
                            <h2>{{ $currentWeek['totalTickets'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket List Modal -->
            <div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="ticketModalLabel">Ticket List</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div id="ticketListLoading" class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <div id="ticketListContent" style="display: none;">
                                <ul class="list-group" id="ticketListItems"></ul>
                            </div>
                            <div id="ticketListEmpty" style="display: none;" class="alert alert-info">
                                No tickets found.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="container" class="chart-container"></div>

            @if(!isset($isFiltered) || !$isFiltered)
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        @if($hasPrevious)
                            <a href="{{ route('tickets.chart', ['week' => $weekIndex - 1]) }}" class="btn btn-secondary">
                                ← Previous Week
                            </a>
                        @else
                            <button class="btn btn-secondary" disabled>← Previous Week</button>
                        @endif
                    </div>

                    <div class="text-center">
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle week-badge" type="button" id="weekDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Week {{ $weekIndex + 1 }} / {{ $totalWeeks }}
                            </button>
                            <ul class="dropdown-menu week-dropdown" aria-labelledby="weekDropdown">
                                @foreach($allWeeks as $week)
                                    <li>
                                        <a class="dropdown-item {{ $week['index'] === $weekIndex ? 'active' : '' }}"
                                            href="{{ route('tickets.chart', ['week' => $week['index']]) }}">
                                            Week {{ $week['weekNumber'] }}: {{ $week['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div>
                        @if($hasNext)
                            <a href="{{ route('tickets.chart', ['week' => $weekIndex + 1]) }}" class="btn btn-secondary">
                                Next Week →
                            </a>
                        @else
                            <button class="btn btn-secondary" disabled>Next Week →</button>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center mt-4">
                    <div class="alert alert-info">
                        <strong>Filtered View:</strong> Showing all data from the selected date range. Use "Clear Filter" to
                        return to weekly pagination.
                    </div>
                </div>
            @endif

            <div class="text-center mt-3">
                <a href="{{ route('home') }}" class="btn btn-primary">
                    Upload Another File
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="{{ asset('js/chart-data.js') }}"></script>
        <script>
            // Pass PHP data to JavaScript
            window.ticketChartData = @json($currentWeek);
            window.isFiltered = {{ isset($isFiltered) && $isFiltered ? 'true' : 'false' }};
            window.weekIndex = {{ $weekIndex }};
            window.weekTickets = @json($weekTickets ?? []);
        </script>
        <script src="{{ asset('js/tickets-chart.js') }}"></script>
        <script src="{{ asset('js/ticket-list.js') }}"></script>
    @endpush
@endsection