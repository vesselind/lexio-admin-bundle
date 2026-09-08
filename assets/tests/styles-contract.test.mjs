import assert from 'node:assert/strict';
import {existsSync, readFileSync} from 'node:fs';
import {join, resolve} from 'node:path';
import {test} from 'node:test';
import {fileURLToPath} from 'node:url';
import * as sass from 'sass';

const assetsDirectory = resolve(fileURLToPath(new URL('..', import.meta.url)));

const compileEntry = (entry, configuration = '') => sass.compileString(
    `@use '${entry}'${configuration};`,
    {
        loadPaths: [assetsDirectory, join(assetsDirectory, 'node_modules')],
        quietDeps: true,
        style: 'compressed',
    },
).css;

test('publishes configurable Sass source without precompiled CSS alternatives', () => {
    const adminEntry = readFileSync(join(assetsDirectory, 'styles', 'admin.scss'), 'utf8');
    const componentsEntry = readFileSync(join(assetsDirectory, 'styles', 'components.scss'), 'utf8');

    assert.match(adminEntry, /bootstrap\/scss\/bootstrap/);
    assert.match(componentsEntry, /admin\/components\/components/);
    assert.doesNotMatch(componentsEntry, /bootstrap\/scss\/bootstrap/);

    for (const file of ['admin.css', 'components.css', join('styles', 'vanilla_datepicker.css')]) {
        assert.equal(existsSync(join(assetsDirectory, 'dist', file)), false, file);
    }

    const css = compileEntry('styles/admin', ' with ($lexio-admin-primary: #d345ec)');
    assert.match(css, /\.btn-primary/);
    assert.match(css, /--bs-btn-bg:\s*#d345ec/);
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
    assert.equal(packageJson.scripts['build:styles'], undefined);
    assert.equal(packageJson.files.includes('build-styles.mjs'), false);
    assert.equal(packageJson.scripts.test, 'node --test tests/controllers-contract.test.mjs tests/styles-contract.test.mjs');
});

test('compiled Sass exposes the documented runtime theme properties', () => {
    const css = compileEntry('styles/admin');

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

test('components Sass exposes the image selector card component', () => {
    const source = readFileSync(join(assetsDirectory, 'styles', 'admin', 'components', '_components.scss'), 'utf8');
    const css = compileEntry('styles/components');

    assert.match(source, /@import "input-image-selector"/);
    assert.match(css, /\.input-image-selector/);
    assert.match(css, /border:2px dashed/);
});

test('uses namespaced Sass built-ins', () => {
    const variables = readFileSync(join(assetsDirectory, 'styles', 'admin', '_variables.scss'), 'utf8');
    const ownedStylesheets = [
        'styles/admin/_variables.scss',
        'styles/admin/mixins/background-variant.scss',
        'styles/admin/components/buttons.scss',
        'styles/admin/utilities/toasts-colors.scss',
        'src/styles/vanilla_datepicker.scss',
    ];

    assert.match(variables, /@use ['"]sass:string['"]/);
    assert.match(variables, /\$breadcrumb-divider:\s+string\.quote\(["']\/["']\)/);
    assert.doesNotMatch(variables, /\$breadcrumb-divider:\s+quote\(/);

    for (const stylesheet of ownedStylesheets) {
        const source = readFileSync(join(assetsDirectory, stylesheet), 'utf8');

        for (const builtIn of ['quote', 'mix', 'darken', 'lighten', 'saturate']) {
            assert.doesNotMatch(source, new RegExp(`(^|[^.\\w])${builtIn}\\(`), `${stylesheet}: ${builtIn}`);
        }
    }
});
