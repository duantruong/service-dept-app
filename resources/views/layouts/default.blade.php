<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Service Department App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/smc.css') }}">
    <style>
    :root {
        --brand-blue: #003A70;
        --brand-green: #009639;
    }
    </style>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')

</head>

<body>
    <div class="d-flex justify-content-center align-items-center min-vh-100 vw-100 mainbody"
        style="background: linear-gradient(135deg, var(--brand-blue), var(--brand-green));">
        @yield('maincontent')

    </div>
</body>

</html>