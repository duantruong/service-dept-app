@extends('layouts/default')

@section('maincontent')
    <div class="container mt-5">
        <h1>Upload Excel File</h1>
        <p>Upload an Excel file (.xlsx or .xls) to generate a ticket chart.</p>

        <form action="{{ route('tickets.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">Select Excel File</label>
                <input type="file" class="form-control" id="file" name="file" accept=".xlsx,.xls" required>
                <div class="form-text">Max file size: 20MB</div>
            </div>

            <button type="submit" class="btn btn-primary">Upload and Generate Chart</button>
            <a href="{{ route('home') }}" class="btn btn-secondary">Back to Home</a>
        </form>
    </div>
@endsection