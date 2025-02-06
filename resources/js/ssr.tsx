import ReactDOMServer from 'react-dom/server';
import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { route } from '../../vendor/tightenco/ziggy/dist/index';
import { RouteName } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pagePath = import.meta.glob('./Pages/**/*.tsx');

createServer((page) =>
  createInertiaApp({
    page,
    render: ReactDOMServer.renderToString,
    title: (title) => `${title ? title + ' - ' : ' '}${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.tsx`, pagePath),
    setup: ({ App, props }) => {
      (globalThis as any).route = (
        name: RouteName,
        params?: any,
        absolute?: boolean,
      ) =>
        route(name, params, absolute, {
          // @ts-expect-error
          ...page.props.ziggy,
          // @ts-expect-error
          location: new URL(page.props.ziggy.location),
        });

      return <App {...props} />;
    },
  }),
);
