import React, { useEffect, useRef, useState } from 'react';


export function LinesWidget({ sport }) {
    if (!sport) {
        return <div style={{ opacity: 0.6 }}>Выберите спорт слева — тогда покажу линию.</div>;
    }


    const versionRef = useRef(null);
    const etagRef = useRef(null);

    const timerRef = useRef(null);
    const flashTimerRef = useRef(null);

    const prevOddsRef = useRef(new Map()); // id -> { odd_1, odd_x, odd_2 }
    const hasLoadedRef = useRef(false);

    const [loading, setLoading] = useState(true);
    const [items, setItems] = useState([]);
    const [version, setVersion] = useState(null);

    // changed[id] = { odd_1: true, odd_x: true, odd_2: true }
    const [changed, setChanged] = useState({});

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

    async function loadData() {
        const res = await fetch(`/api/lines?sport=${encodeURIComponent(sport)}`);

        if (res.status === 304) return;

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

            if (Object.keys(ch).length) {
                changes[r.id] = ch;
            }
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

    async function tick() {
        try {
            const headers = {};
            if (etagRef.current) headers['If-None-Match'] = etagRef.current;

            const res = await fetch(
                `/api/lines/version?sport=${encodeURIComponent(sport)}`,
                { headers }
            );

            if (res.status === 200) {
                etagRef.current = res.headers.get('ETag');
                const meta = await res.json();

                const nextVersion = meta?.version ?? 0;

                // если версия изменилась или мы еще ни разу не загрузили данные
                if (versionRef.current !== nextVersion || !hasLoadedRef.current) {
                    versionRef.current = nextVersion;
                    setVersion(nextVersion);
                    await loadData();
                }
            }
            // 304 => ничего не делаем
        } finally {
            const base = document.hidden ? 15000 : 5000; // 5s активно, 15s в фоне
            const jitter = Math.floor(Math.random() * 250); // небольшой джиттер
            timerRef.current = setTimeout(tick, base + jitter);
        }
    }

    useEffect(() => {
        // сбрасываем состояние при смене sport
        versionRef.current = null;
        etagRef.current = null;
        prevOddsRef.current = new Map();
        hasLoadedRef.current = false;

        setLoading(true);
        setItems([]);
        setVersion(null);
        setChanged({});

        tick();

        return () => {
            if (timerRef.current) clearTimeout(timerRef.current);
            if (flashTimerRef.current) clearTimeout(flashTimerRef.current);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [sport]);

    const rows = [...items].sort(
        (a, b) => new Date(a.commence_time) - new Date(b.commence_time)
    );

    if (loading) return <div style={{ opacity: 0.6 }}>React: загружаю…</div>;

    return (
        <div>
            <div style={{ fontSize: 12, opacity: 0.6, marginBottom: 8 }}>
                sport: {sport} / version: {version ?? '-'} / rows: {rows.length}
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
