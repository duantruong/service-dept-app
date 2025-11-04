@extends('layouts.default')

@section('maincontent')
    <div class="d-flex flex-column align-items-center position-relative">
        @if (session('error'))
            <div class="alert alert-danger" id="errorAlert"
                style="position: absolute; top: -80px; width: 22rem; z-index: 1000;">
                {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" id="successAlert"
                style="position: absolute; top: -80px; width: 22rem; z-index: 1000;">
                {{ session('success') }}
            </div>
        @endif
        <div class="card shadow p-4" style="width: 22rem; border-radius: 1rem; background-color: #ffffff; ">
            <div class="d-flex flex-column align-items-center" style="width: 18rem;">
                <img src="{{ asset('images/supermicro-logo.png') }}" alt=" Supermicro Logo"
                    class="img-fluid mx-auto d-block" style="max-height: 80px; width: auto;" />
            </div>

            <h3 class="text-center mb-3">Register</h3>
            <form action="{{ route('register.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label" style="color: var(--brand-blue);">
                        Name
                    </label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                        value="{{ old('name') }}" placeholder="Enter your name" required />
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label" style="color: var(--brand-blue);">
                        Email address
                    </label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                        value="{{ old('email') }}" placeholder="Enter email" required />
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label" style="color: var(--brand-blue);">
                        Password
                    </label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                        name="password" placeholder="Enter password" required />
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label" style="color: var(--brand-blue);">
                        Confirm Password
                    </label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                        placeholder="Confirm password" required />
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Register
                </button>
            </form>

            <div class="mt-3 text-center">
                <p style="color: var(--brand-blue);">
                    Already have an account?
                    <a href="{{ route('login') }}" style="color: var(--brand-green);">Login here</a>
                </p>
            </div>
        </div>
    </div>
@endsection