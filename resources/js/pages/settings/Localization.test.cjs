const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const Module = require('node:module');
const ts = require('typescript');
const React = require('react');
const { renderToStaticMarkup } = require('react-dom/server');

function renderLocalization() {
    const filename = __dirname + '/Localization.tsx';
    const source = fs.readFileSync(filename, 'utf8');
    const output = ts.transpileModule(source, { compilerOptions: { jsx: ts.JsxEmit.ReactJSX, module: ts.ModuleKind.CommonJS } }).outputText;
    const loaded = new Module(filename, module);
    loaded.filename = filename;
    loaded.paths = module.paths;
    loaded.require = (id) => {
        if (id === '@inertiajs/react') return { Head: () => null };
        if (id === '@/components/migration/common') return { SubmitForm: ({ children }) => React.createElement('form', null, children) };
        return module.require(id);
    };
    loaded._compile(output, filename);
    return renderToStaticMarkup(React.createElement(loaded.exports.default, {
        localization: { language: 'en', timezone: 'UTC', date_format: 'Y-m-d', time_format: 'H:i', number_format: '1,234.56', currency: 'USD', currency_display: 'symbol' },
        timezones: ['UTC'],
    }));
}

test('currency and display are editable localization fields', () => {
    const html = renderLocalization();
    assert.match(html, /<input[^>]*name="currency"[^>]*>/);
    assert.doesNotMatch(html, /<input[^>]*type="hidden"[^>]*name="currency"/);
    assert.match(html, /<select[^>]*name="currency_display"/);
    assert.match(html, /<option value="code"/);
});
