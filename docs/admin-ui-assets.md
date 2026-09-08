# Admin asset package

The bundle publishes `assets/package.json` as `@lexio/admin-bundle`. Its
Symfony UX metadata is the compatibility contract for the 26 packaged
Stimulus identifiers. `assets/src/controllers/` is the customizable source;
`assets/dist/controllers/` contains the committed distributable entry points.

The bundle also owns reusable admin Sass under `assets/styles/`. Consumers must
compile either `@lexio/admin-bundle/styles/admin` for Bootstrap plus bundle
styles or `@lexio/admin-bundle/styles/components` when the host compiles
Bootstrap separately. The bundle does not publish precompiled CSS because it
cannot provide complete Bootstrap theme customization.

Lexio-specific Sass variables use the public `$lexio-admin-*` namespace. The
full `styles/admin` entry also accepts native Bootstrap Sass variables such as
`$primary`, `$body-bg`, `$spacer`, `$border-radius`, and `$theme-colors` for
compile-time host control. Bootstrap variable compatibility follows the
bundle's supported Bootstrap peer range. The same Lexio defaults are emitted
as `--lexio-admin-*` custom properties on `:root` and
`[data-lexio-admin-theme]`, allowing runtime overrides for the documented
properties consumed by bundle-owned rules. Bootstrap components must be
configured through Sass before compilation.

The starter compiles the public Sass entry from its
`assets/styles/admin-theme.scss` brand entry and registers all 26 package
controllers in `assets/controllers.json`. The starter keeps its two
public-site-only controllers (`onscroll` and `rating`) locally; it no longer contains
copied bundle controller source, CKEditor helpers, admin Sass, or generic admin
Twig/Maker templates. Its public-site flash stylesheet and datepicker locale
helper remain host-owned.

The starter application registers the package in Encore. The Stimulus bridge
loads the package definitions through `controllers.json`, and the bundle
package is now the only owner of the reusable admin controllers and structural
styles.

## Upload and CAPTCHA readiness

The controllers only transport values supplied by server-rendered markup:
upload URLs, redirect/frame URLs, flash URLs, CSRF tokens, and CAPTCHA site or
script configuration. They do not make authorization decisions or validate
files in the browser.

Before enabling the package in a host, each endpoint must be reviewed on the
server for:

- authorization on image, file, and CKEditor upload actions;
- CSRF protection appropriate to the endpoint and request format;
- MIME, size, extension, and upload-error validation before persistence;
- safe redirect/frame targets and response handling; and
- CAPTCHA secret validation on the server, without exposing secrets to the
  browser.

This review remains an explicit migration gate because the current starter
upload endpoints do not share one CSRF contract.
