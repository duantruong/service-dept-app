@extends('layouts.default')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/forms.css') }}">
@endpush

@section('maincontent')
    <div class="d-flex flex-column align-items-center position-relative">
        @if ($errors->any())
            <div class="alert alert-danger error-alert" id="errorAlert">
                @foreach ($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif
        <div class="card shadow p-4 upload-card">
            <h1 class="text-center mb-2 welcome-title">Welcome, {{ session('user_name') }}!</h1>
            <p class="text-center mb-2">You have successfully logged in.</p>
            <p class="text-center mb-3 upload-instruction">Please upload the file.</p>
            <h3 class="text-center mb-3">Upload XLSX File</h3>
            <form method="post" action="{{ route('tickets.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3 text-center">
                    <label for="file" class="btn btn-outline-primary w-100 file-upload-label">
                        <input type="file" class="d-none" id="file" name="file" accept=".xlsx,.xls" required>
                        <span class="file-name">Choose File</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    Generate Chart
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/file-upload.js') }}"></script>
    @endpush
@endsection