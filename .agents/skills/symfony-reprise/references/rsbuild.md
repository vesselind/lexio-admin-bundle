# Rsbuild equivalents

Vite is the default choice, but everything Reprise does works identically under Rsbuild. The only differences are the config file shape and the dev/build commands. Read `SKILL.md` for the concepts; this file has the Rsbuild snippets.

## Install

```bash
yarn add --dev @symfony/reprise @rsbuild/core
# or npm: npm install @symfony/reprise @rsbuild/core --save-dev
```

## Quickstart config

```ts
// rsbuild.config.ts
import { defineConfig } from '@rsbuild/core'
import Symfony from '@symfony/reprise/rsbuild'

export default defineConfig({
  source: {
    entry: {
      app: './assets/app.js',
    },
  },
  plugins: [
    Symfony({
      // options
    }),
  ],
})
```

The Twig side is identical to Vite — same `reprise_entry_*` functions, same `entrypoints.json`/`manifest.json` output. The only dev difference: under Vite, `reprise_entry_script_tags` injects the HMR client automatically; under Rsbuild the client is compiled into the bundle.

## npm scripts

```jsonc
"scripts": {
  "dev-server": "rsbuild dev",
  "dev": "rsbuild build --watch",
  "build": "rsbuild build"
}
```

## Plugin options

Same options as the Vite plugin, in the same places:

```ts
Symfony({
  stimulus: 'assets/controllers.json',
  copy: [{ from: 'assets/images', to: 'images' }],
  integrity: { enabled: command === 'build', algorithms: ['sha384'] },
  outputPath: 'public/build',
  publicPath: '/build/',
  manifestKeyPrefix: 'build/',   // required with an absolute publicPath
})
```

## Mode-dependent options

Rsbuild's function form passes `command` as `'dev'` or `'build'` (Vite uses `'serve'`/`'build'`):

```ts
// rsbuild.config.ts  (command is 'dev' or 'build')
import { defineConfig } from '@rsbuild/core'
import Symfony from '@symfony/reprise/rsbuild'

export default defineConfig(({ command }) => ({
  source: {
    entry: {
      app: './assets/app.js',
    },
  },
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

## Multiple builds

```ts
// rsbuild.config.widget.ts -- run with `rsbuild build --config rsbuild.config.widget.ts`
import { defineConfig } from '@rsbuild/core'
import Symfony from '@symfony/reprise/rsbuild'

export default defineConfig({
  source: {
    entry: {
      widget: './assets/widget.js',
    },
  },
  plugins: [
    Symfony({
      outputPath: 'public/widget-build',
      publicPath: '/widget-build/',
    }),
  ],
})
```

## Extra bundler plugins

Sass needs a plugin under Rsbuild:

```bash
yarn add --dev @rsbuild/plugin-sass
```

```ts
import { pluginSass } from '@rsbuild/plugin-sass'

export default defineConfig({
  plugins: [
    pluginSass(),
    Symfony({ ... }),
  ],
})
```

Framework presets: `@rsbuild/plugin-react`, `@rsbuild/plugin-vue`, `@rsbuild/plugin-preact`, `@rsbuild/plugin-svelte`.
