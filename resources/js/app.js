import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, Link } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];
        if (!page) {
            throw new Error(`Inertia page not found: ./Pages/${name}.vue`);
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, props.initialPage?.props?.ziggy)
            .component('Link', Link);
        
        // Global error handler
        app.config.errorHandler = (err, instance, info) => {
            console.error('Vue Error:', err);
            console.error('Error Info:', info);
            console.error('Component:', instance);
        };
        
        app.mount(el);
    },
    progress: false,
});
