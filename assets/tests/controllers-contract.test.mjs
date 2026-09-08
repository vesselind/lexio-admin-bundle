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

test('publishes a generated registrar for every packaged controller', () => {
    const registrar = readFileSync(join(assetsDirectory, 'dist', 'register_controllers.js'), 'utf8');

    assert.match(registrar, /export function registerLexioAdminControllers\(application\)/);

    for (const [identifier, metadata] of Object.entries(packageJson.symfony.controllers)) {
        assert.match(registrar, new RegExp(`${JSON.stringify(identifier).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}:`), identifier);

        const controllerImport = `./${metadata.main.replace(/^dist\//, '')}`;

        if (metadata.fetch === 'lazy') {
            assert.match(registrar, new RegExp(`import\\(${JSON.stringify(controllerImport).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\)`), identifier);
        } else {
            assert.match(registrar, new RegExp(`from ${JSON.stringify(controllerImport).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`), identifier);
        }

        for (const [autoimport, enabled] of Object.entries(metadata.autoimport ?? {})) {
            if (enabled) {
                assert.match(registrar, new RegExp(JSON.stringify(autoimport).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), `${identifier}: ${autoimport}`);
            }
        }
    }
});

test('controller registrar registers eager controllers and discovers lazy controllers', async () => {
    const originalDocument = globalThis.document;
    const originalMutationObserver = globalThis.MutationObserver;
    const observedElement = {
        getAttribute: () => 'lazy-controller',
    };
    let observerDisconnected = false;

    globalThis.document = {
        documentElement: {
            getAttribute: () => null,
            querySelectorAll: () => [observedElement],
        },
    };
    globalThis.MutationObserver = class {
        observe() {}

        disconnect() {
            observerDisconnected = true;
        }
    };

    try {
        const {registerControllers} = await import('../src/controller_registrar.js');
        const registered = new Map();
        const application = {
            register(identifier, controller) {
                registered.set(identifier, controller);
                this.router.modulesByIdentifier.set(identifier, controller);
            },
            router: {
                modulesByIdentifier: new Map(),
            },
        };
        class EagerController {}
        class LazyController {}

        const registration = registerControllers(
            application,
            {'eager-controller': EagerController},
            {'lazy-controller': async () => LazyController},
        );

        await new Promise((resolve) => setImmediate(resolve));

        assert.equal(registered.get('eager-controller'), EagerController);
        assert.equal(registered.get('lazy-controller'), LazyController);
        assert.equal(observerDisconnected, true);

        registration.disconnect();
    } finally {
        globalThis.document = originalDocument;
        globalThis.MutationObserver = originalMutationObserver;
    }
});

test('publishes an explicit Bootstrap runtime entry for admin consumers', () => {
    const source = readFileSync(join(assetsDirectory, 'src', 'bootstrap.js'), 'utf8');
    const dist = readFileSync(join(assetsDirectory, 'dist', 'bootstrap.js'), 'utf8');

    assert.match(source, /import ['"]bootstrap['"]/);
    assert.match(dist, /import ['"]bootstrap['"]/);
});

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

test('image selection keeps the relation ID and visual preview in sync', () => {
    const selectorSource = readFileSync(join(sourceDirectory, 'input_image_selector_controller.js'), 'utf8');
    const gallerySource = readFileSync(join(sourceDirectory, 'image_gallery_controller.js'), 'utf8');

    assert.match(selectorSource, /static targets = \['input', 'card', 'previewContainer'/);
    assert.match(selectorSource, /const imageId = event\.detail\?\.imageId/);
    assert.match(selectorSource, /const imageUrl = event\.detail\?\.imageUrl/);
    assert.match(selectorSource, /this\.inputTarget\.value = imageId/);
    assert.match(selectorSource, /this\.previewTarget\.src = imageUrl/);
    assert.doesNotMatch(selectorSource, /this\.inputTarget\.value = imagePath/);
    assert.doesNotMatch(selectorSource, /valueMode/);
    assert.doesNotMatch(selectorSource, /imagePath/);
    assert.match(selectorSource, /new Event\('input', \{bubbles: true\}\)/);
    assert.match(selectorSource, /new Event\('change', \{bubbles: true\}\)/);
    assert.match(gallerySource, /imageName: imageName/);
    assert.match(gallerySource, /imageUrl: imageUrl/);
    assert.doesNotMatch(gallerySource, /imagePath/);
});

test('sidebar submenu headers toggle their targeted Bootstrap collapse instance', () => {
    const source = readFileSync(join(sourceDirectory, 'collapsable_sidebar_controller.js'), 'utf8');

    assert.match(source, /toggle\(event\)/);
    assert.match(source, /event\.stopPropagation\(\)/);
    assert.match(source, /getAttribute\('data-bs-target'\)/);
    assert.match(source, /this\.bsCollapse\[targetId\]\.toggle\(\)/);
});

test('runtime dependencies are peers and controller source has no starter-app endpoints', () => {
    for (const dependency of [
        '@hotwired/stimulus',
        '@hotwired/turbo',
        '@symfony/ux-live-component',
        '@popperjs/core',
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
