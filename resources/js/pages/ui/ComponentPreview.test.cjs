const test = require('node:test');
const assert = require('node:assert/strict');
const path = require('node:path');
const React = require('react');
const { renderToStaticMarkup } = require('react-dom/server');
const catalog = require('../../../ui-catalog.json');

void test('each migrated catalog entry renders a React preview', async () => {
    const { createServer } = await import('vite');
    const server = await createServer({
        configFile: false,
        appType: 'custom',
        server: { middlewareMode: true },
        resolve: { alias: { '@': path.resolve(__dirname, '../..') } },
    });

    try {
        const additional = await server.ssrLoadModule(
            '/resources/js/pages/ui/previews/additional.tsx',
        );
        const display = await server.ssrLoadModule(
            '/resources/js/pages/ui/previews/display.tsx',
        );
        const forms = await server.ssrLoadModule(
            '/resources/js/pages/ui/previews/forms.tsx',
        );
        const navigation = await server.ssrLoadModule(
            '/resources/js/pages/ui/previews/navigation-layout.tsx',
        );
        const previews = {
            ...additional.additionalPreviews,
            ...display.displayPreviews,
            ...forms.formPreviews,
            ...navigation.navigationLayoutPreviews,
        };

        for (const entry of catalog.filter(
            (entry) => entry.category !== 'Shared',
        )) {
            const Preview = previews[entry.slug];
            assert.equal(
                typeof Preview,
                'function',
                `Missing preview for ${entry.slug}`,
            );
            assert.ok(
                renderToStaticMarkup(React.createElement(Preview)),
                `Empty preview for ${entry.slug}`,
            );
        }

        for (const slug of ['button', 'badge']) {
            const html = renderToStaticMarkup(
                React.createElement(previews[slug]),
            );
            for (const variant of [
                'default',
                'secondary',
                'destructive',
                'outline',
                'ghost',
                'link',
            ]) {
                assert.match(
                    html,
                    new RegExp(`data-variant="${variant}"`),
                    `${slug} is missing the ${variant} variant`,
                );
            }
        }
    } finally {
        await server.close();
    }
});
