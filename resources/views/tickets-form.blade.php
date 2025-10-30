<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Upload tickets</title>
</head>

<body>
    <h1>Upload XLSX</h1>
    @if ($errors->any())
        <div style="color:red">@foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach</div>
    @endif
    <form method="post" action="{{ route('tickets.upload') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".xlsx,.xls" required>
        <button>Generate chart</button>
    </form>
</body>

</html>