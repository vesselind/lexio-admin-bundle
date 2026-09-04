import assert from 'node:assert/strict';
import {readFileSync, statSync} from 'node:fs';
import {join, resolve} from 'node:path';
import {test} from 'node:test';
import {fileURLToPath} from 'node:url';

const assetsDirectory = resolve(fileURLToPath(new URL('..', import.meta.url)));

test('publishes the full and components-only Sass entry points', () => {
    const adminEntry = readFileSync(join(assetsDirectory, 'styles', 'admin.scss'), 'utf8');
    const componentsEntry = readFileSync(join(assetsDirectory, 'styles', 'components.scss'), 'utf8');

    assert.match(adminEntry, /bootstrap\/scss\/bootstrap/);
    assert.match(componentsEntry, /admin\/components\/components/);
    assert.doesNotMatch(componentsEntry, /bootstrap\/scss\/bootstrap/);

    for (const file of ['admin.css', 'components.css', join('styles', 'vanilla_datepicker.css')]) {
        assert.ok(statSync(join(assetsDirectory, 'dist', file)).size > 0, file);
    }
});

test('declares Bootstrap and Popper for both host consumption and local Sass builds', () => {
    const packageJson = JSON.parse(readFileSync(join(assetsDirectory, 'package.json'), 'utf8'));

    assert.equal(packageJson.peerDependencies.bootstrap, '^5.3.0');
    assert.equal(packageJson.devDependencies.bootstrap, '^5.3.0');
    assert.equal(packageJson.peerDependencies['@popperjs/core'], '^2.11.8');
    assert.equal(packageJson.devDependencies['@popperjs/core'], '^2.11.8');
});

test('keeps the standalone asset toolchain compatible with the supported Node baseline', () => {
    const packageJson = JSON.parse(readFileSync(join(assetsDirectory, 'package.json'), 'utf8'));

    assert.equal(packageJson.engines.node, '>=18');
    assert.equal(packageJson.devDependencies.sass, '~1.94.2');
    assert.equal(packageJson.devDependencies.vitest, undefined);
    assert.equal(packageJson.scripts.test, 'node --test tests/controllers-contract.test.mjs tests/styles-contract.test.mjs');
});

test('generated CSS exposes the documented runtime theme properties', () => {
    const css = readFileSync(join(assetsDirectory, 'dist', 'admin.css'), 'utf8');

    for (const property of [
        '--lexio-admin-primary',
        '--lexio-admin-body-color',
        '--lexio-admin-font-family',
        '--lexio-admin-radius',
        '--lexio-admin-sidebar-width',
        '--lexio-admin-header-logo-width',
    ]) {
        assert.match(css, new RegExp(property));
    }
});

test('generated CSS exposes the image selector card component', () => {
    const source = readFileSync(join(assetsDirectory, 'styles', 'admin', 'components', '_components.scss'), 'utf8');
    const css = readFileSync(join(assetsDirectory, 'dist', 'components.css'), 'utf8');

    assert.match(source, /@import "input-image-selector"/);
    assert.match(css, /\.input-image-selector/);
    assert.match(css, /border:2px dashed/);
});
