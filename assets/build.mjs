import { readdir } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const assetsDirectory = dirname(fileURLToPath(import.meta.url));
const sourceDirectory = join(assetsDirectory, 'src', 'controllers');
const outputDirectory = join(assetsDirectory, 'dist', 'controllers');

// Keep all runtime libraries external. They are declared as peer dependencies
// and must be supplied by the host application's asset pipeline.
const external = [
    '@hotwired/stimulus',
    '@hotwired/turbo',
    '@symfony/ux-live-component',
    'bootstrap',
    'ckeditor5',
    'debounce',
    'sortablejs',
    'tom-select',
    'vanillajs-datepicker',
];

const esbuildModule = process.env.LEXIO_ADMIN_ESBUILD
    ? pathToFileURL(join(process.env.LEXIO_ADMIN_ESBUILD, 'lib', 'main.js')).href
    : 'esbuild';
const { build } = await import(esbuildModule);
const controllerFiles = (await readdir(sourceDirectory))
    .filter((file) => file.endsWith('_controller.js'))
    .sort();

await Promise.all(controllerFiles.map((file) => build({
    bundle: true,
    entryPoints: [join(sourceDirectory, file)],
    external,
    format: 'esm',
    loader: {
        '.css': 'empty',
        '.scss': 'empty',
    },
    minify: false,
    outfile: join(outputDirectory, file),
    platform: 'browser',
    sourcemap: false,
    target: ['es2020'],
})));
