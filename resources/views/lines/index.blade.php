<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
<div style="max-width:1100px;margin:30px auto;">
    <h1>Lines</h1>

    {{-- Blade-скелет --}}
    <div style="padding:12px;border:1px solid #ddd;border-radius:8px;">
        <div style="opacity:.6">Blade: страница загрузилась, React подтянет данные…</div>

        {{-- React-островок --}}
        <div data-react="lines-widget" data-sport="all" style="margin-top:10px"></div>

    </div>
</div>
</body>
</html>
