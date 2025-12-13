<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>


    </head>
    <body>

        @if (Route::has('lines.index'))
            <a
                href="{{ route('lines.index') }}">
                Wisit lines
            </a>
        @endif

    </body>
</html>
