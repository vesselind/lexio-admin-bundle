# Lexio Admin Bundle assets

This package owns the reusable Stimulus controllers and structural styles used
by the bundle's admin templates. It follows Symfony UX metadata conventions so
Reprise (Vite/Rsbuild), Encore, and AssetMapper consumers can discover the same
controller identifiers.

The identifiers and Stimulus value names are compatibility contracts. Existing
hosts may keep their current `data-controller` attributes while switching the
package registration from local source files to this package. New behavior must
be configured through Stimulus values or server-rendered attributes; controllers
must not embed host route names, entity classes, translation messages, or
authorization decisions.

Controllers used by the admin shell (`modal`, `base-modal`, `autosubmit`,
`navigate-turbo`, `collapsable-sidebar`, and `flash-message`) are
marked eager. Feature-specific controllers are lazy and are downloaded only
when their identifier is present. The metadata is the source of truth for this
policy.

`turnstile` is the Cloudflare Turnstile controller. `captcha` is the Google
reCAPTCHA Enterprise controller; it loads Google's public script and obtains a
fresh token at form submission. The bundle form types render the site-key/action
Stimulus values; the host validates both providers' tokens on the server.

The public-site-only `onscroll` and `rating` controllers belong to the host
application and are intentionally not included in this package.

Build the distributable JavaScript with `yarn build` from this directory. The
committed files under `dist/controllers/` are the JavaScript package entry
points. Styles are published only as Sass source under `styles/` so every host
compiles Bootstrap and the admin theme from the same configuration.

The package also publishes `dist/bootstrap.js` as the admin JavaScript runtime
entry. It initializes the Bootstrap peer dependency once for admin pages;
consumers should import this entry instead of importing Bootstrap separately in
their admin assembly entrypoint.

`dist/register_controllers.js` is generated from the `symfony.controllers`
metadata by `build.mjs`. Call `registerLexioAdminControllers(application)` from
the admin entry to register eager controllers immediately and lazy controllers
only when their identifier appears in the DOM. Reprise hosts can therefore keep
only public-site bundle controllers in `assets/controllers.json` without
maintaining a second admin controller manifest.

## Styles

Consumers must select one Sass entry:

- `@lexio/admin-bundle/styles/admin` compiles Bootstrap 5 and all reusable
  admin styles in one entry;
- `@lexio/admin-bundle/styles/components` compiles only the bundle's
  components for a host that owns Bootstrap configuration.

A Sass-capable host build is required. Lexio-specific Sass inputs use the
stable `$lexio-admin-*` prefix. The full `styles/admin` entry also accepts
native Bootstrap Sass variables for hosts that need compile-time control over
Bootstrap's theme and component maps. Compiling the entry exposes the
documented Lexio values as `--lexio-admin-*` properties on `:root` and
`[data-lexio-admin-theme]` for bundle rules that consume runtime properties.

A Reprise host (Vite or Rsbuild) may customize the compiled source and load
application overrides after it:

```scss
@use '@lexio/admin-bundle/styles/admin' with (
  $lexio-admin-primary: #4f46e5,
  $lexio-admin-font-family: (Inter, sans-serif),
  $lexio-admin-sidebar-width: 18rem,
  $lexio-admin-radius: 0.625rem
);

@use 'admin-theme';
```

The host owns product logos, favicons, marketing fonts, public-site styles,
and product-specific icon choices. Font Awesome CDN usage remains a separate
compatibility task until the admin templates have been migrated to UX Icons.

For a Reprise host, install the package as `@lexio/admin-bundle`, list only the
bundle controllers required by public pages in `assets/controllers.json`, and
call the generated registrar from the admin entry. The package should be the
only owner of reusable admin controller and structural style source. Keep only
host brand tokens, public-site styles, and product locale helpers in the
application. Local controllers live in `assets/controllers/` and are
auto-discovered by Reprise; the controllers directory must not re-declare the
bundle-owned identifiers.
