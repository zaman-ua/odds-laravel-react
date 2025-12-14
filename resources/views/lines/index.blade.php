<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
<div style="margin:30px auto;">
    <h1>Lines</h1>

    {{-- Blade-скелет --}}
    <div style="padding:12px;border:1px solid #ddd;border-radius:8px;">
        <div style="opacity:.6">Blade: страница загрузилась, React подтянет данные…</div>

        <div style="display:flex; gap:14px; margin-top:10px;">
            {{-- Левая колонка (Blade, статично) --}}
            <div style="width:260px; border-right:1px solid #eee; padding-right:12px;">
                <div style="font-size:12px; opacity:.6; margin-bottom:8px;">Sports</div>

                <div style="max-height:70vh; overflow-y:auto;">
                    @foreach($groups as $g)
                        <details class="sport-group" style="margin-bottom:10px;">
                            <summary
                                style="
                            list-style:none;
                            cursor:pointer;
                            padding:8px 10px;
                            border:1px solid #eee;
                            border-radius:8px;
                            background:#fafafa;
                            user-select:none;
                        "
                            >
                                <span style="font-weight:600;">{{ $g['group'] }}</span>
                                <span style="opacity:.6; font-size:12px; margin-left:6px;">
                            ({{ count($g['sports']) }})
                        </span>
                            </summary>

                            <div style="margin-top:8px; padding-left:8px;">
                                @foreach($g['sports'] as $s)
                                    <button
                                        type="button"
                                        class="sport-item"
                                        data-key="{{ $s['key'] }}"
                                        style="
                                    display:block;
                                    width:100%;
                                    text-align:left;
                                    padding:8px 10px;
                                    margin-bottom:6px;
                                    border-radius:8px;
                                    border:1px solid #eee;
                                    background:#fff;
                                    cursor:pointer;
                                "
                                    >
                                        {{ $s['title'] }}
                                    </button>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>

            {{-- Правая часть (React таблица) --}}
            <div style="flex:1; min-width:0;">
                <div data-react="lines-widget"></div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
