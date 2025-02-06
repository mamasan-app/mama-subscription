import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });

createInertiaApp({
  title: (title) => `${title ? `${title} - ` : ''}${appName}`,
  resolve: (name) => pages[`./Pages/${name}.tsx`],
  setup({ el, App, props }) {
    const root = createRoot(el);
    root.render(<App {...props} />);
  },
  progress: {
    color: '#4B5563',
  },
});
