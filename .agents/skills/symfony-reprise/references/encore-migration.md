# Migrating from Webpack Encore

Reprise is Webpack Encore's successor for Vite and Rsbuild, so the move splits in two: the Symfony side barely changes, and `webpack.config.js` mostly goes away (Vite/Rsbuild do natively what most `Encore.*` calls set up — delete those rather than translate them).

## The Symfony side

Reprise ships its own `RepriseBundle` and does not use `WebpackEncoreBundle`, so swap the Composer package:

```
composer remove symfony/webpack-encore-bundle
composer require symfony/reprise
```

In your templates the Twig functions keep the same shape, with the `encore_` prefix becoming `reprise_`:

```twig
{# before #}
{{ encore_entry_link_tags('app') }}
{{ encore_entry_script_tags('app') }}

{# after #}
{{ reprise_entry_link_tags('app') }}
{{ reprise_entry_script_tags('app') }}
```

The bundle config carries over almost key-for-key: `webpack_encore.output_path` becomes `reprise.output_path`, and `crossorigin`, `preload`, `cache`, `strict_mode`, `script_attributes` and `link_attributes` all exist under `reprise` with the same meaning. Encore's `builds` option maps to multiple builds.

## Your build config

Most of `webpack.config.js` has no equivalent — the bundler already does the work. The Symfony glue Encore layered on top of Webpack stays, as a Reprise plugin option:

| Webpack Encore | Reprise |
| :--- | :--- |
| `setOutputPath()` / `setPublicPath()` | plugin `outputPath` / `publicPath` (defaults fit a standard project) |
| `setManifestKeyPrefix()` | plugin `manifestKeyPrefix` (see CDN) |
| `addEntry()` / `addEntries()` | Vite `input` (Vite 8.1 and older: `build.rollupOptions.input`); Rsbuild `source.entry` |
| `enableVersioning()` | nothing to do, content hashing is on by default |
| `enableIntegrityHashes()` | plugin `integrity: { enabled: true }` |
| `copyFiles()` | plugin `copy: [ ... ]` |
| `enableStimulusBridge()` | plugin `stimulus: 'assets/controllers.json'` |
| `configureDevServerOptions()` | nothing to do: run `vite` or `rsbuild dev` and Reprise points Twig at it |

## Things the bundler now handles (delete these calls)

| Webpack Encore | Now handled by the bundler |
| :--- | :--- |
| `enableSassLoader()` / `enableLessLoader()` / `enableStylusLoader()` | install the preprocessor and import the file (Vite out of the box, Rsbuild via `@rsbuild/plugin-sass` and friends) |
| `enablePostCssLoader()` | add a `postcss.config.js`, picked up automatically |
| `enableTypeScriptLoader()` / `configureBabel()` / `configureBabelPresetEnv()` | native transpilation (Vite via esbuild, Rsbuild via SWC); set targets with `browserslist` or `build.target` |
| `enableForkedTypeScriptTypesChecking()` / `enableBabelTypeScriptPreset()` | run `tsc --noEmit` (or `vue-tsc`) as its own script, outside the build |
| `splitEntryChunks()` / `configureSplitChunks()` / `addCacheGroup()` | native code splitting |
| `enableSingleRuntimeChunk()` / `disableSingleRuntimeChunk()` | native runtime chunk management |
| `enableSourceMaps()` | native (Vite `build.sourcemap`) |
| `configureImageRule()` / `configureFontRule()` / `configureFilenames()` | native asset handling and output naming |
| `addStyleEntry()` | add the stylesheet as an entry input, or import it from a JS entry |
| `configureDefinePlugin()` | Vite `define`, Rsbuild `source.define` |
| `disableCssExtraction()` / `configureCssLoader()` / `configureStyleLoader()` | native CSS handling (extraction, minification) |
| `addAliases()` | `resolve.alias` |
| `addExternals()` | Vite `build.rollupOptions.external`, Rsbuild `output.externals` |
| `enableBuildCache()` / `configureWatchOptions()` | native build caching and watch |
| `cleanupOutputBeforeBuild()` | native (Vite `build.emptyOutDir`, Rsbuild cleans by default) |

## Framework presets become the bundler's own plugin

| Webpack Encore | Vite | Rsbuild |
| :--- | :--- | :--- |
| `enableReactPreset()` | `@vitejs/plugin-react` | `@rsbuild/plugin-react` |
| `enableVueLoader()` | `@vitejs/plugin-vue` | `@rsbuild/plugin-vue` |
| `enablePreactPreset()` | `@preact/preset-vite` | `@rsbuild/plugin-preact` |
| `enableSvelte()` | `@sveltejs/vite-plugin-svelte` | `@rsbuild/plugin-svelte` |

## Features with no direct replacement

- `autoProvideVariables()` / `autoProvidejQuery()`: prefer importing what you use. To inject a global anyway, use `@rollup/plugin-inject` under Vite or `rspack.ProvidePlugin` (through Rsbuild's `tools.rspack`).
- `enableBuildNotifications()` and the ESLint integration are gone: run your linter as its own script, outside the build.
- `Encore.isProduction()` / `isDev()` / `isDevServer()` / `when()`: branch on the bundler mode instead, e.g. `defineConfig(({ command }) => ...)` where `command` is `'build'` or the dev command.
- `addLoader()` / `addRule()` / `addPlugin()` / `configureLoaderRule()`: escape hatches into raw Webpack config; edit the bundler config directly (Vite `plugins`, Rsbuild `tools.rspack`).
- `enableHandlebarsLoader()`: add the bundler's own Handlebars plugin if you still need it.
- `configureRuntimeEnvironment()` / `clearRuntimeEnvironment()` / `isRuntimeEnvironmentConfigured()` / `reset()`: Encore's own bootstrap and reset plumbing, no counterpart.
- The remaining `configure*Plugin()` calls (`configureManifestPlugin()`, `configureMiniCssExtractPlugin()`, `configureCssMinimizerPlugin()`, `configureJsMinimizerPlugin()`, `configureFriendlyErrorsPlugin()`): tuned Webpack internals that Reprise and the bundlers now own; nothing to port.
