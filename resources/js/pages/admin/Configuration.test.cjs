const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const Module = require('node:module');
const ts = require('typescript');
const React = require('react');
const { renderToStaticMarkup } = require('react-dom/server');

function renderConfiguration(
    settings,
    defaultRoleOptions = [],
    emergency = 'logout',
) {
    const filename = __dirname + '/Configuration.tsx';
    const source = fs.readFileSync(filename, 'utf8');
    const output = ts.transpileModule(source, {
        compilerOptions: {
            jsx: ts.JsxEmit.ReactJSX,
            module: ts.ModuleKind.CommonJS,
        },
    }).outputText;
    const loaded = new Module(filename, module);
    loaded.filename = filename;
    loaded.paths = module.paths;
    const passthrough = ({ children }) => children;
    loaded.require = (id) => {
        if (id === 'react')
            return {
                ...React,
                useState: (initial) =>
                    React.useState(initial === 'logout' ? emergency : initial),
            };
        if (id === '@/components/migration/common')
            return {
                Screen: passthrough,
                SubmitForm: ({ children, action }) =>
                    React.createElement('form', { action }, children),
                Field: ({ name, required }) =>
                    React.createElement('input', { name, required }),
            };
        if (id === '@/components/ui/select')
            return {
                Select: ({ name, value, defaultValue, children }) =>
                    React.createElement(
                        React.Fragment,
                        null,
                        name &&
                            React.createElement('input', {
                                type: 'hidden',
                                name,
                                value: value ?? defaultValue ?? '',
                                readOnly: true,
                            }),
                        children,
                    ),
                SelectTrigger: passthrough,
                SelectValue: () => null,
                SelectContent: passthrough,
                SelectItem: passthrough,
            };
        return module.require(id);
    };
    loaded._compile(output, filename);
    return renderToStaticMarkup(
        React.createElement(loaded.exports.default, {
            settings,
            defaultRoleOptions,
        }),
    );
}

void test('configuration selects submit existing values and the emergency default', () => {
    const html = renderConfiguration(
        {
            general: {
                enabled: false,
                admin_theme: 'dark',
                default_role: 'editor',
            },
        },
        [{ name: 'Editor', slug: 'editor' }],
    );
    assert.match(html, /name="enabled" value="0"/);
    assert.match(html, /name="admin_theme" value="dark"/);
    assert.match(html, /name="default_role" value="editor"/);
    assert.match(html, /name="action" value="logout"/);
    assert.doesNotMatch(html, /name="reason"/);
});

void test('lockdown requires a reason and submits the selected emergency action', () => {
    const html = renderConfiguration({ general: {} }, [], 'lockdown');
    assert.match(html, /name="action" value="lockdown"/);
    assert.match(html, /<input required="" name="reason"/);
});

void test('missing setting values fall back to the first available option', () => {
    const html = renderConfiguration(
        { general: { admin_theme: null, default_role: null } },
        [{ name: 'Editor', slug: 'editor' }],
    );
    assert.match(html, /name="admin_theme" value="light"/);
    assert.match(html, /name="default_role" value="editor"/);
});
