import {execFileSync} from 'node:child_process';
import {readFileSync, readdirSync, statSync} from 'node:fs';
import {join, resolve} from 'node:path';
import {test} from 'node:test';
import assert from 'node:assert/strict';
import {fileURLToPath} from 'node:url';

const assetsDirectory = resolve(fileURLToPath(new URL('..', import.meta.url)));
const packageJson = JSON.parse(readFileSync(join(assetsDirectory, 'package.json'), 'utf8'));
const sourceDirectory = join(assetsDirectory, 'src', 'controllers');
const distDirectory = join(assetsDirectory, 'dist', 'controllers');

test('every packaged controller has explicit Symfony UX metadata and builds', () => {
    const sourceFiles = readdirSync(sourceDirectory)
        .filter((file) => file.endsWith('_controller.js'))
        .sort();
    const controllers = packageJson.symfony.controllers;

    assert.equal(Object.keys(controllers).length, sourceFiles.length);

    for (const [identifier, metadata] of Object.entries(controllers)) {
        assert.equal(metadata.name, identifier, identifier);
        assert.equal(metadata.enabled, true, identifier);
        assert.ok(['eager', 'lazy'].includes(metadata.fetch), identifier);
        assert.equal(metadata.webpackMode, metadata.fetch, identifier);
        assert.match(metadata.main, /^dist\/controllers\/.+_controller\.js$/, identifier);
        assert.ok(statSync(join(assetsDirectory, metadata.main)), metadata.main);

        const sourceFile = metadata.main.replace('dist/controllers/', 'src/controllers/');
        assert.ok(statSync(join(assetsDirectory, sourceFile)), sourceFile);
        execFileSync(process.execPath, ['--check', join(assetsDirectory, sourceFile)], {stdio: 'pipe'});
        execFileSync(process.execPath, ['--check', join(assetsDirectory, metadata.main)], {stdio: 'pipe'});
    }
});

test('Turnstile uses a provider-specific Symfony UX controller identifier', () => {
    const metadata = packageJson.symfony.controllers.turnstile;

    assert.equal(metadata.name, 'turnstile');
    assert.equal(metadata.main, 'dist/controllers/turnstile_controller.js');

    const source = readFileSync(join(sourceDirectory, 'turnstile_controller.js'), 'utf8');
    assert.match(source, /apiKey:\s*String/);
    assert.match(source, /window\.turnstile/);
});

test('Google reCAPTCHA Enterprise uses a generic captcha identifier and refreshes on submit', () => {
    const metadata = packageJson.symfony.controllers.captcha;

    assert.equal(metadata.name, 'captcha');
    assert.equal(metadata.main, 'dist/controllers/captcha_controller.js');

    const source = readFileSync(join(sourceDirectory, 'captcha_controller.js'), 'utf8');
    assert.match(source, /enterprise\.js/);
    assert.match(source, /data-google-recaptcha-enterprise/);
    assert.match(source, /connect\(\)\s*\{\s*this\.element\.value = '';/);
    assert.match(source, /form\.addEventListener\('submit', this\.onSubmit, true\)/);
    assert.match(source, /event\.preventDefault\(\)/);
    assert.match(source, /this\.isResubmitting/);
    assert.match(source, /const token = await this\.execute\(\)/);
    assert.match(source, /this\.form\.requestSubmit\(event\.submitter\)/);
});

test('runtime dependencies are peers and controller source has no starter-app endpoints', () => {
    for (const dependency of [
        '@hotwired/stimulus',
        '@hotwired/turbo',
        '@symfony/ux-live-component',
        'bootstrap',
        'ckeditor5',
        'debounce',
        'sortablejs',
        'tom-select',
        'vanillajs-datepicker',
    ]) {
        assert.ok(packageJson.peerDependencies[dependency], dependency);
    }

    const source = readdirSync(sourceDirectory)
        .filter((file) => file.endsWith('.js'))
        .map((file) => readFileSync(join(sourceDirectory, file), 'utf8'))
        .join('\n');

    assert.doesNotMatch(source, /fetch\(\s*["']\/flash["']/);
    assert.doesNotMatch(source, /admin\.[a-z_]+/);
    assert.doesNotMatch(source, /window\.bootstrap/);
});
