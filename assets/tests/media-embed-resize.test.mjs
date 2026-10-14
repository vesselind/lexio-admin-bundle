import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';
import {test} from 'node:test';
import assert from 'node:assert/strict';

const source = readFileSync(new URL('../src/CKEditor/MediaEmbedResize.js', import.meta.url), 'utf8')
    .replace(/import\s*\{[\s\S]*?\}\s*from 'ckeditor5';/, '')
    .replace('export default class MediaEmbedResize', 'globalThis.MediaEmbedResize = class MediaEmbedResize');

for (const [language, expected] of [
    ['en', ['Resize media', 'Original', 'Custom…', 'Resize media (50%)', 'Media width (for example 50% or 560px):']],
    ['bg', ['Преоразмеряване на медия', 'Оригинален размер', 'По избор…', 'Преоразмеряване на медия (50%)', 'Ширина на медията (например 50% или 560px):']],
]) {
    test(`media resize controls use the ${language} editor locale`, () => {
        // The same flat YAML dictionaries supplied by the bundle's form type.
        const dictionary = Object.fromEntries(readFileSync(new URL(`../../translations/ckeditor.${language}.yaml`, import.meta.url), 'utf8')
            .split(/\r?\n/).filter(line => line.trim() && !line.startsWith('#'))
            .map(line => {
                const separator = line.indexOf(':');
                return [line.slice(0, separator), JSON.parse(line.slice(separator + 1).trim())];
            }));
        const locale = {t: (key, value) => (dictionary[key] ?? key).replace('%0', value ?? '')};
        let factory;
        let items;
        let execute;
        let widthPrompt;
        const calls = [];
        const buttonView = {
            set(values) { Object.assign(this, values); },
            bind() { return {to: (command, property, callback) => { this.currentWidthLabel = callback; }}; },
        };
        const dropdown = {
            buttonView,
            bind() { return {to() {}}; },
            on(event, callback) { execute = callback; },
        };
        const context = {
            Plugin: class { constructor(editor) { this.editor = editor; } },
            Command: class {},
            MediaEmbed: class {},
            Collection: class extends Array { add(item) { this.push(item); } },
            ViewModel: class { constructor(values) { Object.assign(this, values); } },
            createDropdown: () => dropdown,
            addListToDropdown: (view, collection) => { items = collection; },
            prompt: message => { widthPrompt = message; return '50%'; },
        };
        runInNewContext(source, context);
        const editor = {
            locale,
            commands: {get: () => ({})},
            ui: {componentFactory: {add: (name, callback) => { factory = callback; }}},
            execute: (...args) => calls.push(args),
        };
        const UI = context.MediaEmbedResize.requires[1];
        new UI(editor).init();
        factory(locale);

        assert.equal(buttonView.label, expected[0]);
        assert.equal(items[0].model.label, expected[1]);
        assert.equal(items[4].model.label, expected[2]);
        assert.equal(buttonView.currentWidthLabel('50%'), expected[3]);
        execute({source: {commandParam: 'custom'}});
        assert.equal(widthPrompt, expected[4]);
        assert.equal(calls[0][0], 'resizeMediaEmbed');
        assert.equal(calls[0][1].width, '50%');
    });
}
