import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const source = readFileSync(new URL('../src/controllers/captcha_controller.js', import.meta.url), 'utf8')
    .replace("import { Controller } from '@hotwired/stimulus';", '')
    .replace('export default class extends Controller', 'class CaptchaController extends Controller');
const settle = () => new Promise((resolve) => setImmediate(resolve));

class Element extends EventTarget {
    constructor(document) {
        super();
        this.ownerDocument = document;
        this.dataset = {};
        this.attributes = {};
        this.isConnected = true;
        this.value = '';
    }
    setAttribute(name, value) { this.attributes[name] = value; }
    remove() {
        this.isConnected = false;
        this.ownerDocument.scripts = this.ownerDocument.scripts.filter((script) => script !== this);
    }
    closest() { return this.form; }
}

function fixture() {
    const timers = new Set();
    const CaptchaController = runInNewContext(`${source}\nCaptchaController;`, {
        Controller: class {}, AbortController, URL,
        setTimeout(callback) { const timer = {callback}; timers.add(timer); return timer; },
        clearTimeout(timer) { timers.delete(timer); },
    });
    const document = {
        baseURI: 'https://example.test/contact',
        defaultView: new EventTarget(),
        scripts: [],
        createElement() { return new Element(this); },
        querySelector() { return this.scripts[0] ?? null; },
    };
    document.head = {appendChild(script) { document.scripts.push(script); }};
    const controllers = [];
    return {
        document, timers,
        controller(siteKey = 'test-site-key', action = 'contact') {
            const form = new EventTarget();
            form.isConnected = true;
            form.submissions = [];
            form.requestArguments = [];
            form.prepend = (element) => { form.error = element; };
            const input = new Element(document);
            input.form = form;
            form.submit = (submitter = null) => {
                const event = new Event('submit', {cancelable: true});
                event.submitter = submitter;
                form.dispatchEvent(event);
                if (!event.defaultPrevented) {
                    form.submissions.push({token: input.value, submitter});
                }
                return event;
            };
            form.requestSubmit = (...args) => {
                form.requestArguments.push(args);
                form.submit(args[0]);
            };
            const controller = new CaptchaController();
            Object.assign(controller, {
                element: input, siteKeyValue: siteKey, actionValue: action,
                scriptUrlValue: 'https://www.google.com/recaptcha/api.js',
                errorMessageValue: 'Please try submitting again.',
            });
            controller.connect();
            controllers.push(controller);
            return controller;
        },
        load(api) {
            document.defaultView.grecaptcha = api;
            document.scripts[0]?.dispatchEvent(new Event('load'));
        },
        cleanup() {
            controllers.forEach((controller) => controller.disconnect());
            document.scripts[0]?.dispatchEvent(new Event('error'));
        },
    };
}

function api(execute = () => Promise.resolve('token')) {
    return {ready(callback) { callback(); }, execute};
}

test('two fields load one classic script for their document', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    f.controller(); f.controller('test-site-key', 'login');
    assert.equal(f.document.scripts.length, 1);
    assert.equal(f.document.scripts[0].src, 'https://www.google.com/recaptcha/api.js?render=test-site-key');
    f.load(api());
    await settle();
    assert.equal(f.timers.size, 0);
});

test('submit creates a fresh action token and preserves the submit button', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    const c = f.controller();
    let calls = 0;
    f.load(api((key, options) => {
        assert.equal(key, 'test-site-key'); assert.equal(options.action, 'contact');
        return Promise.resolve(`token-${++calls}`);
    }));
    const button = {form: c.form, name: 'send', value: 'contact'};
    c.form.submit(button);
    await settle();
    assert.equal(c.form.submissions.length, 1);
    assert.equal(c.form.submissions[0].token, 'token-1');
    assert.equal(c.form.submissions[0].submitter, button);
    c.form.submit(button);
    await settle();
    assert.equal(calls, 1);
    const restored = new Event('pageshow'); restored.persisted = true;
    f.document.defaultView.dispatchEvent(restored);
    assert.equal(c.element.value, '');
    c.form.submit(button);
    await settle();
    assert.equal(c.form.submissions[1].token, 'token-2');
});

test('keyboard submission calls requestSubmit without a null button', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    const c = f.controller(); f.load(api());
    c.form.submit(); await settle();
    assert.deepEqual(c.form.requestArguments, [[]]);
    assert.equal(c.form.submissions.length, 1);
});

test('multiple clicks while a token is pending produce one submission', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    const c = f.controller();
    let resolveToken; let calls = 0;
    f.load(api(() => { ++calls; return new Promise((resolve) => { resolveToken = resolve; }); }));
    c.form.submit(); c.form.submit(); await settle();
    assert.equal(calls, 1);
    resolveToken('fresh-token'); await settle();
    assert.equal(c.form.submissions.length, 1);
});

test('token failure is visible and the next submission can retry', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    const c = f.controller(); let calls = 0;
    f.load(api(() => ++calls === 1 ? Promise.reject(new Error('offline')) : Promise.resolve('retry-token')));
    c.form.submit(); await settle();
    assert.equal(c.form.submissions.length, 0);
    assert.equal(c.form.error.attributes.role, 'alert');
    assert.equal(c.form.error.textContent, 'Please try submitting again.');
    c.form.submit(); await settle();
    assert.equal(c.form.submissions[0].token, 'retry-token');
});

test('a missing site key blocks submission with a visible error', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    const c = f.controller('');
    c.form.submit(); await settle();
    assert.equal(f.document.scripts.length, 0);
    assert.equal(c.form.submissions.length, 0);
    assert.equal(c.form.error.textContent, 'Please try submitting again.');
});

test('failed script loading is cleaned up and can be retried', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    const c = f.controller();
    f.document.scripts[0].dispatchEvent(new Event('error')); await settle();
    assert.equal(f.document.scripts.length, 0);
    c.form.submit(); await settle();
    assert.equal(f.document.scripts.length, 1);
    f.load(api()); await settle();
    assert.equal(c.form.submissions.length, 1);
});

test('disconnect removes listeners and ignores a late token after reconnect', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    const c = f.controller(); let resolveToken; let calls = 0;
    f.load(api(() => ++calls === 1 ? new Promise((resolve) => { resolveToken = resolve; }) : Promise.resolve('new-token')));
    c.form.submit(); await settle();
    c.disconnect();
    assert.equal(f.timers.size, 0);
    c.connect();
    resolveToken('old-token'); await settle();
    assert.equal(c.form.submissions.length, 0);
    c.form.submit(); await settle();
    assert.equal(c.form.submissions[0].token, 'new-token');
});

test('cancelled form submission requires another fresh token', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    const c = f.controller(); let calls = 0;
    f.load(api(() => Promise.resolve(`token-${++calls}`)));
    const rejectSubmission = (event) => { if (c.element.value) event.preventDefault(); };
    c.form.addEventListener('submit', rejectSubmission);
    c.form.submit(); await settle();
    assert.equal(c.form.submissions.length, 0);
    assert.equal(c.element.value, '');
    c.form.removeEventListener('submit', rejectSubmission);
    c.form.submit(); await settle();
    assert.equal(c.form.submissions[0].token, 'token-2');
});

test('a stalled Google ready callback times out with a visible error', async (t) => {
    const f = fixture(); t.after(() => f.cleanup());
    const c = f.controller();
    f.load({ready() {}, execute() { throw new Error('must not execute'); }});
    c.form.submit(); await settle();
    for (const timer of [...f.timers]) timer.callback();
    await settle();
    assert.equal(c.form.submissions.length, 0);
    assert.equal(c.form.error.textContent, 'Please try submitting again.');
    assert.equal(f.timers.size, 0);
});
