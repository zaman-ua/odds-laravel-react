import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { LinesWidget } from './components/LinesWidget';

document.querySelectorAll('[data-react="lines-widget"]').forEach((el) => {
    const sport = el.dataset.sport ?? 'soccer';
    createRoot(el).render(<LinesWidget sport={sport} />);
});
