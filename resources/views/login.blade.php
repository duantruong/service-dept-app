@extends('layouts/default')

@section('maincontent')
    <div class="d-flex flex-column align-items-center position-relative">
        @if (session('error'))
            <div class="alert alert-danger" id="errorAlert"
                style="position: absolute; top: -80px; width: 22rem; z-index: 1000;">
                {{ session('error') }}
            </div>
        @endif
        <div class="card shadow p-4" style="width: 22rem; border-radius: 1rem; background-color: #ffffff; ">
            <div class="d-flex flex-column align-items-center" style="width: 18rem;">
                <img src="{{ asset('images/supermicro-logo.png') }}" alt=" Supermicro Logo"
                    class="img-fluid mx-auto d-block" style="max-height: 80px; width: auto;" />
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
                    <label for="password" class="form-label" style="color: var(--brand-blue);">
                        Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password"
                        required />
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" />
                    <label class="form-check-label" for="rememberMe" style="color: var(--brand-blue);">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Login
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const errorAlert = document.getElementById('errorAlert');
            if (errorAlert) {
                setTimeout(function () {
                    errorAlert.style.transition = 'opacity 0.5s ease-out';
                    errorAlert.style.opacity = '0';
                    setTimeout(function () {
                        errorAlert.remove();
                    }, 500);
                }, 5000);
            }
        });
    </script>
@endsection