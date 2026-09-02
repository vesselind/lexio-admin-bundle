import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const assetsDirectory = dirname(fileURLToPath(import.meta.url));
const outputDirectory = join(assetsDirectory, 'dist');

// The bundle owns the source and distributable CSS. During development the
// host can provide its installed Sass runtime through LEXIO_ADMIN_SASS.
const sassModule = process.env.LEXIO_ADMIN_SASS
    ? pathToFileURL(join(process.env.LEXIO_ADMIN_SASS, 'sass.node.js')).href
    : 'sass';
const importedSass = await import(sassModule);
const { compile } = importedSass.default ?? importedSass;

const loadPaths = [
    join(assetsDirectory, 'node_modules'),
    ...(process.env.LEXIO_ADMIN_NODE_MODULES ? [process.env.LEXIO_ADMIN_NODE_MODULES] : []),
];

const entries = [
    ['styles/admin.scss', 'admin.css'],
    ['styles/components.scss', 'components.css'],
    ['styles/vanilla_datepicker.scss', join('styles', 'vanilla_datepicker.css')],
];

await mkdir(outputDirectory, { recursive: true });

for (const [entry, output] of entries) {
    const result = compile(join(assetsDirectory, entry), {
        loadPaths,
        style: 'compressed',
        sourceMap: false,
    });

    const outputPath = join(outputDirectory, output);
    await mkdir(dirname(outputPath), { recursive: true });
    await writeFile(outputPath, result.css, 'utf8');
}
