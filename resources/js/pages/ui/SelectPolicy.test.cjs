const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

void test('application JSX uses the shared Select instead of native select elements', () => {
    const root = path.resolve(__dirname, '../..');
    const violations = [];

    function inspect(directory) {
        for (const entry of fs.readdirSync(directory, {
            withFileTypes: true,
        })) {
            const filename = path.join(directory, entry.name);
            if (entry.isDirectory()) {
                inspect(filename);
            } else if (entry.name.endsWith('.tsx')) {
                const source = fs.readFileSync(filename, 'utf8');
                if (
                    /<select(?:\s|>)/.test(source) ||
                    /@\/components\/ui\/native-select/.test(source)
                ) {
                    violations.push(path.relative(root, filename));
                }
            }
        }
    }

    inspect(path.join(root, 'components'));
    inspect(path.join(root, 'pages'));
    assert.deepEqual(violations, [], 'Use the shared shadcn Select component');
});
