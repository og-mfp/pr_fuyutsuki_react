const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const Module = require('node:module');
const ts = require('typescript');
const React = require('react');
const { renderToStaticMarkup } = require('react-dom/server');

test('unverified users can request a verification email with POST', () => {
    const filename = __dirname + '/profile.tsx';
    const code = ts.transpileModule(fs.readFileSync(filename, 'utf8'), { compilerOptions: { jsx: ts.JsxEmit.ReactJSX, module: ts.ModuleKind.CommonJS, esModuleInterop: true } }).outputText;
    const loaded = new Module(filename, module);
    loaded.filename = filename;
    loaded.paths = module.paths;
    const passthrough = ({ children }) => typeof children === 'function' ? children({ processing: false, errors: {} }) : children;
    loaded.require = (id) => {
        if (id === '@inertiajs/react') return { Head: () => null, Form: passthrough, Link: ({ children, method }) => React.createElement('a', { 'data-method': method }, children), usePage: () => ({ props: { auth: { user: { name: 'Example', email: 'a@example.com', email_verified_at: null } } } }) };
        if (id.includes('/ProfileController')) return { __esModule: true, default: { update: { form: () => ({}) } } };
        if (id === '@/routes/verification') return { send: () => '/email/verification-notification' };
        if (id.startsWith('@/routes/')) return { edit: () => '/' };
        if (id.startsWith('@/')) return new Proxy({ __esModule: true, default: passthrough }, { get: (target, key) => target[key] || passthrough });
        return module.require(id);
    };
    loaded._compile(code, filename);
    const html = renderToStaticMarkup(React.createElement(loaded.exports.default, { personalInformation: {} }));
    assert.match(html, /data-method="post"[^>]*>Click here to re-send/);
});
