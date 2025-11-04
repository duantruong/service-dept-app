@extends('layouts.default')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/forms.css') }}">
@endpush

@section('maincontent')
    <div class="d-flex flex-column align-items-center position-relative">
        @if (session('error'))
            <div class="alert alert-danger error-alert" id="errorAlert">
                {{ session('error') }}
            </div>
        @endif
        <div class="card shadow p-4 login-card">
            <div class="d-flex flex-column align-items-center logo-container">
                <img src="{{ asset('images/supermicro-logo.png') }}" alt=" Supermicro Logo"
                    class="img-fluid mx-auto d-block logo-img" />
            </div>

            <h3 class="text-center mb-3">Login</h3>
            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label loginInput">
                        Email address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required />
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label form-label-custom">
                        Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password"
                        required />
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" />
                    <label class="form-check-label form-label-custom" for="rememberMe">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Login
                </button>
            </form>
            {{--
            <div class="mt-3 text-center">
                <p style="color: var(--brand-blue);">
                    Don't have an account?
                    <a href="{{ route('register') }}" style="color: var(--brand-green);">Register here</a>
                </p>
            </div>
            --}}
        </div>
    </div>


@endsection