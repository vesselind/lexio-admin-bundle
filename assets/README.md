# Lexio Admin Bundle assets

This package owns the reusable Stimulus controllers and structural styles used
by the bundle's admin templates. It follows Symfony UX metadata conventions so
Encore and AssetMapper consumers can discover the same controller identifiers.

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

Build the distributable files with `yarn build` and `yarn build:styles` from
this directory. The committed files under `dist/controllers/` and
`dist/admin.css` are the package entry points; `src/` and `styles/` remain
available for Encore consumers that intentionally customize the build.

## Styles

Consumers must select one base mode:

- `@lexio/admin-bundle/styles/admin` compiles Bootstrap 5 and all reusable
  admin styles in one entry;
- `@lexio/admin-bundle/styles/components` compiles only the bundle's
  components for a host that owns Bootstrap configuration; or
- `dist/admin.css` can be loaded directly by consumers without Sass; the
  datepicker's `dist/styles/vanilla_datepicker.css` is available separately.

Do not load the full Sass entry and `dist/admin.css` together. Lexio-specific
Sass inputs use the stable `$lexio-admin-*` prefix. The full `styles/admin`
entry also accepts native Bootstrap Sass variables for hosts that need
compile-time control over Bootstrap's theme and component maps. The generated
CSS exposes the documented Lexio values as `--lexio-admin-*` properties on
`:root` and `[data-lexio-admin-theme]`, so those runtime overrides do not
require recompiling.

An Encore host may customize the compiled source and load application
overrides after it:

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

For an Encore host, install the package as `@lexio/admin-bundle` and register
the identifiers from this package in `assets/controllers.json`. The package
registration must use the same explicit `name` values, and the package should
be the only owner of reusable admin controller and structural style source.
Keep only host brand tokens, public-site styles, and product locale helpers in
the application.
