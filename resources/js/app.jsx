import './bootstrap';
import '../css/app.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { LinesApp } from './components/LinesApp';

document.querySelectorAll('[data-react="lines-widget"]').forEach((el) => {
  createRoot(el).render(
    <React.StrictMode>
      <LinesApp />
    </React.StrictMode>
  );
});

// Blade-сайдбар: переключаем active и шлём событие в React
function bindSportsSidebar() {
  const items = Array.from(document.querySelectorAll('.sport-item'));
  if (!items.length) return;

  const setActive = (key) => {
    items.forEach((btn) => {
      const isActive = btn.dataset.key === key;
      btn.classList.toggle('active', isActive);
      btn.style.background = isActive ? '#f1f5ff' : '#fff';

      if (isActive) {
        const details = btn.closest('details');
        if (details) details.open = true; // раскрываем группу выбранного спорта
      }
});
  };

  items.forEach((btn) => {
    btn.addEventListener('click', () => {
      const key = btn.dataset.key;
      setActive(key);

      window.dispatchEvent(new CustomEvent('lines:sport-change', { detail: { sport: key } }));
    });
  });

  // Старт: ничего не выбрано => таблица пустая (как ты хотел)
  setActive(null);
}

bindSportsSidebar();
