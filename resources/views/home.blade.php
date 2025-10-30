@extends('layouts/default')
@section('maincontent')
    <div class="container mt-5">
        <h1>Welcome, {{ session('user_name') }}!</h1>
        <p>You have successfully logged in.</p>

        <a href="{{ route('tickets.form') }}" class="btn btn-primary">
            Upload Excel File & Generate Chart
        </a>
    </div>
@endsection