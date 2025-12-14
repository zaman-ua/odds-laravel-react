import React, { useEffect, useRef, useState } from 'react';

export function LinesWidget({ sport }) {
    const etagRef = useRef(null);

    const timerRef = useRef(null);
    const flashTimerRef = useRef(null);

    const prevOddsRef = useRef(new Map()); // id -> { odd_1, odd_x, odd_2 }
    const hasLoadedRef = useRef(false);

    const [loading, setLoading] = useState(false);
    const [items, setItems] = useState([]);
    const [version, setVersion] = useState(null); // будем хранить ETag/Last-Modified (для дебага)
    const [changed, setChanged] = useState({}); // changed[id] = { odd_1:true, odd_x:true, odd_2:true }

    const normOdd = (v) => {
        if (v === null || v === undefined || v === '') return null;
        const n = Number(v);
        return Number.isFinite(n) ? n : null;
    };

    const fmtOdd = (v) => {
        const n = normOdd(v);
        return n === null ? '-' : n.toFixed(2);
    };

    const fmtTime = (iso) => {
        if (!iso) return '-';
        return new Date(iso).toLocaleString('et-EE', { hour12: false });
    };

    const cellStyle = (isChanged) => ({
        padding: '8px',
        borderBottom: '1px solid #f0f0f0',
        backgroundColor: isChanged ? '#fff7c2' : 'transparent',
        transition: 'background-color 0.3s ease',
        whiteSpace: 'nowrap',
    });

    const fileUrl = (activeSport) =>
        `/lines-cache/${encodeURIComponent(activeSport)}.json`;

    async function loadData(activeSport) {
        const url = fileUrl(activeSport);

        const res = await fetch(url, { cache: 'no-store' });

        // файла нет (ещё не сгенерен)
        if (res.status === 404) {
            setItems([]);
            setLoading(false);
            hasLoadedRef.current = false;
            return;
        }

        // любые ошибки/редиректы
        if (!res.ok) {
            const text = await res.text().catch(() => '');
            console.warn('loadData failed', { url, status: res.status, text: text.slice(0, 200) });
            setItems([]);
            setLoading(false);
            hasLoadedRef.current = false;
            return;
        }

        const ct = res.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            const text = await res.text().catch(() => '');
            console.warn('loadData not json', { url, status: res.status, ct, text: text.slice(0, 200) });
            setItems([]);
            setLoading(false);
            hasLoadedRef.current = false;
            return;
        }

        const data = await res.json();
        const next = Array.isArray(data.items) ? data.items : [];

        // вычисляем изменения относительно prevOddsRef
        const changes = {};
        for (const r of next) {
            if (!r?.id) continue;

            const prev = prevOddsRef.current.get(r.id);
            if (!prev) continue; // первый раз не подсвечиваем

            const n1 = normOdd(r.odd_1);
            const nx = normOdd(r.odd_x);
            const n2 = normOdd(r.odd_2);

            const ch = {};
            if (prev.odd_1 !== n1) ch.odd_1 = true;
            if (prev.odd_x !== nx) ch.odd_x = true;
            if (prev.odd_2 !== n2) ch.odd_2 = true;

            if (Object.keys(ch).length) changes[r.id] = ch;
        }

        // обновляем prev map
        prevOddsRef.current = new Map(
            next
                .filter((r) => r?.id)
                .map((r) => [
                    r.id,
                    {
                        odd_1: normOdd(r.odd_1),
                        odd_x: normOdd(r.odd_x),
                        odd_2: normOdd(r.odd_2),
                    },
                ])
        );

        setItems(next);
        setLoading(false);
        hasLoadedRef.current = true;

        // флэш подсветки
        if (flashTimerRef.current) clearTimeout(flashTimerRef.current);
        setChanged(changes);
        flashTimerRef.current = setTimeout(() => setChanged({}), 1800);
    }

    async function tick(activeSport) {
        try {
            const headers = {};
            if (etagRef.current) headers['If-None-Match'] = etagRef.current;

            const url = fileUrl(activeSport);

            const res = await fetch(url, {
                method: 'HEAD',
                headers,
                cache: 'no-store',
            });

            // файла нет — просто ждём следующий тик
            if (res.status === 404) {
                return;
            }

            if (res.status === 200) {
                // Используем ETag если есть, иначе Last-Modified
                const sig =
                    res.headers.get('ETag') ||
                    res.headers.get('Last-Modified') ||
                    null;

                if (etagRef.current !== sig || !hasLoadedRef.current) {
                    etagRef.current = sig;
                    setVersion(sig); // чисто для отображения
                    await loadData(activeSport);
                }
            }
            // 304 => ничего не делаем
        } finally {
            const base = document.hidden ? 15000 : 5000;
            const jitter = Math.floor(Math.random() * 250);
            timerRef.current = setTimeout(() => tick(activeSport), base + jitter);
        }
    }

    useEffect(() => {
        // очистка таймеров
        if (timerRef.current) clearTimeout(timerRef.current);
        if (flashTimerRef.current) clearTimeout(flashTimerRef.current);

        // сброс состояния при смене sport
        etagRef.current = null;
        prevOddsRef.current = new Map();
        hasLoadedRef.current = false;

        setItems([]);
        setVersion(null);
        setChanged({});

        // если спорт не выбран — не делаем запросов
        if (!sport) {
            setLoading(false);
            return;
        }

        setLoading(true);
        tick(sport);

        return () => {
            if (timerRef.current) clearTimeout(timerRef.current);
            if (flashTimerRef.current) clearTimeout(flashTimerRef.current);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sport]);

    if (!sport) {
        return <div style={{ opacity: 0.6 }}>Выберите спорт слева — тогда покажу линию.</div>;
    }

    if (loading) {
        return <div style={{ opacity: 0.6 }}>React: загружаю…</div>;
    }

    const rows = [...items].sort(
        (a, b) => new Date(a.commence_time) - new Date(b.commence_time)
    );

    return (
        <div>
            <div style={{ fontSize: 12, opacity: 0.6, marginBottom: 8 }}>
                sport: {sport} / etag: {version ?? '-'} / rows: {rows.length}
            </div>

            <div style={{ overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                    <tr>
                        {['Sport', 'Time', 'Home', 'Away', '1', 'X', '2'].map((h) => (
                            <th
                                key={h}
                                style={{
                                    textAlign: 'left',
                                    borderBottom: '1px solid #ddd',
                                    padding: '8px',
                                    whiteSpace: 'nowrap',
                                }}
                            >
                                {h}
                            </th>
                        ))}
                    </tr>
                    </thead>

                    <tbody>
                    {rows.map((r) => (
                        <tr key={r.id ?? `${r.sport_key}-${r.commence_time}-${r.home_team}`}>
                            <td style={cellStyle(false)}>{r.sport_title ?? '-'}</td>
                            <td style={cellStyle(false)}>{fmtTime(r.commence_time)}</td>
                            <td style={cellStyle(false)}>{r.home_team ?? '-'}</td>
                            <td style={cellStyle(false)}>{r.away_team ?? '-'}</td>

                            <td style={cellStyle(!!changed[r.id]?.odd_1)}>{fmtOdd(r.odd_1)}</td>
                            <td style={cellStyle(!!changed[r.id]?.odd_x)}>{fmtOdd(r.odd_x)}</td>
                            <td style={cellStyle(!!changed[r.id]?.odd_2)}>{fmtOdd(r.odd_2)}</td>
                        </tr>
                    ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
