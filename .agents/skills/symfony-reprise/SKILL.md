---
name: symfony-reprise
description: Symfony Reprise is Symfony's native asset integration for Vite (and Rsbuild) — the successor to Webpack Encore. Use this skill whenever the user mentions Symfony Reprise, the symfony/reprise bundle, @symfony/reprise, reprise_entry_script_tags / reprise_entry_link_tags / any reprise_entry_* Twig function, setting up Vite or Rsbuild for a Symfony project, entrypoints.json / manifest.json for Symfony assets, migrating from Webpack Encore to Vite (or Encore to Rsbuild), replacing encore_entry_* with reprise_entry_*, or "how do I build assets in Symfony". Also trigger when a Symfony project has @symfony/reprise in package.json or a vite.config.ts / rsbuild.config.ts, and when the user asks how to wire Stimulus/Symfony UX controllers, file copy, CDN, or SRI hashes into their Symfony asset build. If the question is really about Stimulus controllers, Turbo, or Twig/Live components rather than the build pipeline, prefer the symfony-ux / stimulus / turbo / twig-component / live-component skills.
license: MIT
metadata:
  version: "1.0"
---

# Symfony Reprise

Webpack Encore gave Symfony first-class asset integration for Webpack. Symfony Reprise brings the same to [Vite](https://vite.dev/) and [Rsbuild](https://rsbuild.dev/). Reprise is Encore's successor: same Twig function shapes, same `entrypoints.json`/`manifest.json` format, but driven by a native bundler instead of Webpack.

Official docs: https://symfony.com/bundles/reprise/current/index.html

## Mental model — what Reprise actually does

Vite and Rsbuild already handle Sass/Less/PostCSS, TypeScript, JSX/Vue/Svelte, code splitting, content hashing, source maps, minification and HMR on their own. **Reprise does not reimplement any of that.** It only adds the Symfony-side glue the bundlers leave out:

- `entrypoints.json` + `manifest.json` (Encore-compatible), written in both build and dev-server modes
- Twig functions that render `<script>`/`<link>` tags straight from `entrypoints.json`
- Dev server + HMR wiring (points Twig at the running Vite/Rsbuild server)
- Stimulus/Symfony UX controller registration from `controllers.json`
- File copy into the build, keyed in the manifest
- CDN `publicPath`, Subresource Integrity, multiple builds, tag customization

So the workflow is always: **configure the bundler config file, run the bundler, let Reprise read the JSON it wrote.** Nothing to set up for the common case beyond installing the bundle.

## Installation

```bash
composer require symfony/reprise
```

```bash
yarn add --dev @symfony/reprise vite
# or npm: npm install @symfony/reprise --save-dev && npm install -D vite
```

## Quickstart (Vite — the default choice)

Create `vite.config.ts` at the project root:

```ts
// vite.config.ts
import { defineConfig } from 'vite'
import Symfony from '@symfony/reprise/vite'

export default defineConfig({
  input: {
    app: './assets/app.js',
  },
  plugins: [
    Symfony({
      // options
    }),
  ],
})
```

> Note: on Vite 8.1 and older, configure entries with `build.rollupOptions.input` instead of the top-level `input`.

The defaults fit a standard project: output to `public/build`, public path `/build/`, content hashing on. For Rsbuild equivalents, see `references/rsbuild.md`.

Then render the tags in Twig:

```twig
{# templates/base.html.twig #}
{% block stylesheets %}
    {{ reprise_entry_link_tags('app') }}
{% endblock %}

{% block javascripts %}
    {{ reprise_entry_script_tags('app') }}
{% endblock %}
```

In dev, `reprise_entry_script_tags` injects the Vite HMR client automatically. The output is ESM (`<script type="module">`), matching both Vite and Rsbuild.

### Add npm scripts

There is no `encore`-style Reprise command — you run the bundler directly. Put these in `package.json`:

```jsonc
"scripts": {
  "dev-server": "vite",            // dev server + HMR, Twig auto-pointed at it
  "dev": "vite build --watch",     // rebuild on change (if you don't use the dev server)
  "build": "vite build"            // production build -> public/build
}
```

Run them with `yarn dev-server` / `yarn build` (or `npm run ...`). The same commands work for Rsbuild (`vite` -> `rsbuild`, `vite build` -> `rsbuild build`).

## Rendering asset tags (the Twig functions)

Five functions, identical shape to Encore (prefix `reprise_` instead of `encore_`):

| Webpack Encore | Symfony Reprise | Output |
| :--- | :--- | :--- |
| `encore_entry_link_tags('app')` | `reprise_entry_link_tags('app')` | `<link rel="stylesheet">` tags |
| `encore_entry_script_tags('app')` | `reprise_entry_script_tags('app')` | `<script type="module">` tags |
| `encore_entry_css_files('app')` | `reprise_entry_css_files('app')` | CSS URL list |
| `encore_entry_js_files('app')` | `reprise_entry_js_files('app')` | JS URL list |
| `encore_entry_exists('app')` | `reprise_entry_exists('app')` | `true`/`false` |

- `reprise_entry_js_files` / `reprise_entry_css_files` return raw URL lists instead of HTML — for the rare case you need paths, not tags.
- `reprise_entry_exists('checkout')` returns whether the named entry is present in `entrypoints.json`. Guard a page-specific/optional entry with it so rendering doesn't error in strict mode:
  ```twig
  {% if reprise_entry_exists('checkout') %}
      {{ reprise_entry_script_tags('checkout') }}
  {% endif %}
  ```
- `reprise_entry_script_tags` / `reprise_entry_link_tags` take a fourth `attributes` argument, merged over the configured `script_attributes`/`link_attributes` (per-call wins, `false` drops the attribute). Since it follows the optional entry name, pass it as a named argument:
  ```twig
  {{ reprise_entry_script_tags('app', attributes={ 'data-turbo-track': 'reload' }) }}
  {{ reprise_entry_link_tags('app', attributes={ media: 'print' }) }}
  ```

The tags resolve against Symfony's default asset package — a standard project needs nothing beyond installing the bundle.

## Features (opt-in, via the plugin options)

Each feature turns on through options in `vite.config.ts` (or `rsbuild.config.ts`); leave out the ones you don't need.

### Stimulus / Symfony UX controllers

Point the plugin at your `controllers.json` — that's what enables the feature:

```ts
Symfony({
  stimulus: 'assets/controllers.json',
})
// or, to override the local controllers dir:
Symfony({
  stimulus: {
    controllersJson: 'assets/controllers.json',
    controllersDir: 'assets/controllers',
  },
})
```

Then start the app from your entry:

```js
import { startStimulusApp } from '@symfony/reprise/stimulus'

const app = startStimulusApp()
```

- **Local controllers**: any `assets/controllers/*_controller.{js,ts}` is registered automatically. The filename becomes the identifier (`hello_controller.js` -> `hello`, `admin/user_controller.js` -> `admin--user`).
- **Lazy loading**: put a `stimulusFetch: 'lazy'` comment anywhere in the file (block `/* */`, single-line `//`, or preserved `/*! */` — all work). This is the Vite/Rsbuild counterpart of `@symfony/stimulus-bridge`.
- **Third-party UX packages** (`@symfony/ux-*`) resolve from `node_modules`, installed with your package manager. Some need bundler-specific tweaks (e.g. UX Leaflet Map needs an alias to the plain CSS build); check each package's docs.

### File copy (static assets not imported by JS/CSS)

Assets referenced by stable path from templates — `{{ asset('build/images/logo.svg') }}` — aren't bundled, so copy them into the build and key them in the manifest:

```ts
Symfony({
  copy: [
    {
      from: 'assets/images',  // source dir, relative to project root (required)
      to: 'images',           // destination prefix for the manifest key (required)
    },
  ],
})
```

- `from`/`to` are both required. Empty `to` copies at the root of `outputPath` — use for `favicon.ico` / `site.webmanifest` that must live at a fixed URL.
- `pattern`: regex tested against each file's path relative to `from` to restrict what's copied (default: everything). `includeSubdirectories` defaults to `true`.
- `hash: false` on an entry keeps the file's logical path on disk; the content hash then moves into `manifest.json` as a query string, so `asset()` cache-busting still works. Be aware proxies/CDNs that ignore query strings won't pick up new versions — hashed filenames remain the default.
- Copied files land in `public/build` and are served by the Symfony web server (not the Vite/Rsbuild dev server), so they're available whether or not the dev server is running.

For `asset()` to return the hashed URL, point Symfony's asset component at the generated manifest:

```yaml
# config/packages/framework.yaml
framework:
    assets:
        json_manifest_path: '%kernel.project_dir%/public/build/manifest.json'
```

This is Symfony's native manifest support (the same setting Encore relied on) and applies to every logical asset reference. Entry references Reprise renders already carry their hash and pass through untouched.

By default the manifest lookup is lenient (missing reference returned unchanged). `framework.assets.strict_mode: true` fails loudly instead — but under strict mode, entry references would be rejected too, so route them through a package that skips the manifest: set `reprise.asset_package` to a package with `version: false` and keep `json_manifest_path` on the default package.

### CDN

Serve built assets from a CDN by setting `publicPath` to the absolute CDN URL **for the production build only** (in dev the dev server serves assets directly). Both bundlers expose the mode via the function form of the config, so switch on `command === 'build'`:

```ts
// vite.config.ts  (command is 'serve' or 'build')
import { defineConfig } from 'vite'
import Symfony from '@symfony/reprise/vite'

export default defineConfig(({ command }) => ({
  // ...
  plugins: [
    Symfony({
      publicPath:
        command === 'build'
          ? 'https://my-cool-app.com.global.prod.fastly.net/build/'
          : '/build/',
      manifestKeyPrefix: 'build/',
    }),
  ],
}))
```

With an absolute `publicPath`, `manifestKeyPrefix` is **required** — Reprise can't guess the prefix for the `manifest.json` keys and throws a clear error if it's missing. `entrypoints.json` is rewritten the same way, so the rendered tags point at the CDN. You still have to upload the built files (or set up origin pull). For a CDN subdirectory, include it in the URL.

### Subresource Integrity (SRI)

Add an `integrity` map to `entrypoints.json` so the bundle renders `integrity="..."` on every tag. Only makes sense for production (dev serves changing in-memory assets):

```ts
Symfony({
  integrity: {
    enabled: command === 'build',
    algorithms: ['sha384'],   // optional, defaults to ['sha384']
  },
})
```

Accepted algorithms: `'sha256'`, `'sha384'`, `'sha512'`. Passing several writes multiple space-separated hashes per file (browser accepts "any one of these"). Hashes cover every referenced file per entry (js, css, preloaded/dynamic chunks) and are computed from the files actually written to disk, so they stay correct through minification and hashing. Combine with `reprise.crossorigin` in the bundle config.

### Multiple builds (separate bundler configs)

Several areas of one app (public part, admin panel) are usually just separate **entry points** in one config — don't reach for multiple builds for that. Use multiple builds only when a part needs its own bundler config and output directory, like an embeddable widget.

Give the widget its own config pointing at a separate output directory:

```ts
// vite.config.widget.ts -- run with `vite build --config vite.config.widget.ts`
import { defineConfig } from 'vite'
import Symfony from '@symfony/reprise/vite'

export default defineConfig({
  input: {
    widget: './assets/widget.js',
  },
  plugins: [
    Symfony({
      outputPath: 'public/widget-build',
      publicPath: '/widget-build/',
    }),
  ],
})
```

Name that directory in the bundle config:

```yaml
# config/packages/reprise.yaml
reprise:
    output_path: '%kernel.project_dir%/public/build'
    builds:
        widget: '%kernel.project_dir%/public/widget-build'
```

In Twig, pass the build name; omit it for the default build:

```twig
{{ reprise_entry_script_tags('app') }}                         {# default build #}
{{ reprise_entry_script_tags('widget', build='widget') }}      {# named build #}
```

The `build` argument works on every `reprise_entry_*` function. Each build has its own dev server (different ports, each injecting its HMR client once). Set `output_path: false` for a named-builds-only setup.

### Customizing rendered tags (RenderAssetTagEvent)

Before Reprise writes any `<script>`/`<link>` tag (entry files, CSS, and the dev-server tags it injects itself, like the Vite HMR client), it dispatches a `RenderAssetTagEvent`. A listener can read and mutate `$event->attributes`. Example — stamping a CSP nonce on every tag:

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Reprise\Event\RenderAssetTagEvent;

#[AsEventListener]
final class CspNonceListener
{
    public function __construct(private NonceGenerator $nonceGenerator)
    {
    }

    public function __invoke(RenderAssetTagEvent $event): void
    {
        $event->attributes['nonce'] = $this->nonceGenerator->getNonce();
    }
}
```

## Bundle configuration (config/packages/reprise.yaml)

All options at their defaults:

```yaml
reprise:
    # Directory the @symfony/reprise plugin writes entrypoints.json and manifest.json into.
    # Set to false when using only named builds (requires at least one entry under builds).
    output_path: '%kernel.project_dir%/public/build'

    # Additional named builds: map of build name -> output directory.
    builds: {}

    # Throw when entrypoints.json or a requested entry is missing, instead of rendering nothing.
    strict_mode: true

    # Cache the parsed entrypoints.json in a compiled PHP file, warmed at cache:warmup (needs symfony/cache).
    cache: false

    # A framework.assets package name used to resolve entry URLs. null uses the default package.
    asset_package: null

    # crossorigin attribute set alongside SRI integrity: false, 'anonymous' or 'use-credentials'.
    crossorigin: false

    # Register rendered assets as WebLink HTTP/2 Link: preload headers (needs symfony/web-link).
    preload: true

    # Default attributes added to every rendered <script> / <link> tag.
    script_attributes: []
    link_attributes: []
```

Key details:

- `output_path` must match the plugin's own `outputPath`.
- `cache: true` parses `entrypoints.json` once at `cache:warmup` and reads it from a compiled PHP file at runtime. Enable in production; run `cache:clear` after rebuilding assets. Needs `composer require symfony/cache`.
- `asset_package`: only needed if your default asset package applies a version strategy (which would re-hash files Reprise already content-hashed and break URLs). Point it at a package with `version: false`:
  ```yaml
  reprise:
      asset_package: reprise

  framework:
      assets:
          packages:
              reprise:
                  version: false
  ```
- `script_attributes` / `link_attributes`: maps of default attributes, e.g. `defer: true` or `data-turbo-track: reload`.

## Migrating from Webpack Encore

Reprise is Encore's successor, so the Symfony side barely changes; your `webpack.config.js` mostly goes away. See `references/encore-migration.md` for the full mapping. The essentials:

**Symfony side:**

```bash
composer remove symfony/webpack-encore-bundle
composer require symfony/reprise
```

- Twig: `encore_entry_*` -> `reprise_entry_*` (same shapes, see the table above).
- Bundle config: `webpack_encore.output_path` -> `reprise.output_path`; `crossorigin`, `preload`, `cache`, `strict_mode`, `script_attributes`, `link_attributes` carry over with the same meaning; Encore's `builds` maps to multiple builds.

**Build side:**

- `webpack.config.js` is deleted and replaced by `vite.config.ts`.
- `Encore.addEntry()` -> Vite `input` (or `build.rollupOptions.input` on Vite 8.1 and older).
- `Encore.enableStimulusBridge()` -> plugin `stimulus: 'assets/controllers.json'`.
- `Encore.copyFiles()` -> plugin `copy: [...]`.
- `Encore.enableIntegrityHashes()` -> plugin `integrity: { enabled: true }`.
- `Encore.enableVersioning()` -> nothing: content hashing is on by default.
- `Encore.configureDevServerOptions()` -> nothing: run `vite` and Reprise points Twig at it.
- Everything else (Sass/TS/Babel loaders, split chunks, source maps, aliases, externals, CSS extraction, framework presets) is native bundler work — delete those calls rather than translate them.
- `package.json`: `encore dev` -> `vite`, `encore dev-server` -> `vite`, `encore production` -> `vite build`.

## Verification checklist

After setting up or migrating, confirm:

1. `yarn vite` starts and Twig pages load scripts from the dev server (HMR client injected automatically).
2. `yarn vite build` produces `public/build/entrypoints.json` + `manifest.json`.
3. Twig renders correct `<script>`/`<link>` tags (check `reprise_entry_exists` guards on optional entries).
4. If using `asset()` for copied files, `framework.assets.json_manifest_path` points at the generated manifest.
5. If `strict_mode: true` and production errors on unknown entries, route entries through a `version: false` package via `reprise.asset_package`.
