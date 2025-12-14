import React, {useEffect, useState} from 'react';
import {LinesWidget} from './LinesWidget';

export function LinesApp() {
    const [sport, setSport] = useState(null); // пока не выбран — таблица пустая

    useEffect(() => {
        const handler = (e) => {
            const next = e?.detail?.sport ?? null;
            setSport(next);
        };

        window.addEventListener('lines:sport-change', handler);
        return () => window.removeEventListener('lines:sport-change', handler);
    }, []);

    return <LinesWidget sport={sport}/>;
}
