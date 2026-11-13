const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const Module = require('node:module');
const ts = require('typescript');
const React = require('react');
const { renderToStaticMarkup } = require('react-dom/server');
const triggerIds = [];

function renderLocalization(selections = {}) {
    triggerIds.length = 0;
    const filename = __dirname + '/Localization.tsx';
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
    loaded.require = (id) => {
        if (id === '@inertiajs/react') return { Head: () => null };
        if (id === '@/components/migration/common')
            return {
                SubmitForm: ({ children, action, method }) =>
                    React.createElement(
                        'form',
                        { action, 'data-method': method },
                        children,
                    ),
            };
        if (id === '@/components/ui/select')
            return {
                Select: ({ children, name, defaultValue }) =>
                    React.createElement(
                        'select',
                        {
                            name,
                            defaultValue: selections[name] ?? defaultValue,
                        },
                        children,
                    ),
                SelectTrigger: ({ id }) => {
                    triggerIds.push(id);
                    return null;
                },
                SelectValue: () => null,
                SelectContent: ({ children }) => children,
                SelectItem: ({ children, value }) =>
                    React.createElement('option', { value }, children),
            };
        return module.require(id);
    };
    loaded._compile(output, filename);
    return renderToStaticMarkup(
        React.createElement(loaded.exports.default, {
            localization: {
                language: 'en',
                timezone: 'UTC',
                date_format: 'Y-m-d',
                time_format: 'H:i',
                number_format: '1,234.56',
                currency: 'USD',
                currency_display: 'symbol',
            },
            timezones: ['UTC', 'Asia/Jakarta'],
        }),
    );
}

function submittedValues(html) {
    const values = {};
    for (const match of html.matchAll(
        /<select name="([^"]+)">([\s\S]*?)<\/select>/g,
    )) {
        const selected = match[2].match(/<option value="([^"]*)" selected="">/);
        values[match[1]] = selected?.[1];
    }
    values.currency = html.match(
        /<input[^>]*name="currency"[^>]*value="([^"]*)"/,
    )?.[1];
    return values;
}

void test('localization form exposes the initial values for submission', () => {
    const html = renderLocalization();
    assert.match(
        html,
        /<form data-method="patch" action="\/settings\/localization">/,
    );
    assert.deepEqual(submittedValues(html), {
        language: 'en',
        timezone: 'UTC',
        date_format: 'Y-m-d',
        time_format: 'H:i',
        number_format: '1,234.56',
        currency_display: 'symbol',
        currency: 'USD',
    });
    assert.doesNotMatch(html, /<input[^>]*type="hidden"[^>]*name="currency"/);
    for (const id of [
        'language',
        'timezone',
        'date_format',
        'time_format',
        'number_format',
        'currency_display',
    ]) {
        assert.ok(triggerIds.includes(id));
        assert.match(html, new RegExp(`<label for="${id}">`));
    }
});

void test('localization form exposes changed selections for submission', () => {
    const html = renderLocalization({
        language: 'id',
        timezone: 'Asia/Jakarta',
        date_format: 'd/m/Y',
        time_format: 'h:i A',
        number_format: '1.234,56',
        currency_display: 'code',
    });
    assert.deepEqual(submittedValues(html), {
        language: 'id',
        timezone: 'Asia/Jakarta',
        date_format: 'd/m/Y',
        time_format: 'h:i A',
        number_format: '1.234,56',
        currency_display: 'code',
        currency: 'USD',
    });
});
