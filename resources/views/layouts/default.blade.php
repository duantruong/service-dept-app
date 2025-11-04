<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Service Department App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body>
    @php
        $currentRoute = request()->route()->getName();
    @endphp
    @if($currentRoute !== 'login')
        <nav class="navbar navbar-expand-lg navbar-light shadow-sm fixed-top">
            <div class="container-fluid">
                <a href="{{ route('home') }}" class="navbar-brand">
                    <img src="{{ asset('images/supermicro-logo.png') }}" alt="Supermicro Logo" class="img-fluid" />
                </a>
                <div class="mx-auto">
                    <h4 class="text-white mb-0">Service Department</h4>
                </div>
                @if(session('logged_in'))
                    <form action="{{ route('logout') }}" method="POST" class="d-flex">
                        @csrf
                        <button type="submit" class="btn btn-outline-light">
                            Logout
                        </button>
                    </form>
                @else
                    <div class="navbar-spacer"></div>
                @endif
            </div>
        </nav>
    @endif
    <div
        class="d-flex justify-content-center align-items-center min-vh-100 mainbody @if($currentRoute !== 'login') with-navbar @endif">
        @yield('maincontent')
    </div>
    @stack('scripts')
</body>

</html>