# Admin UI Ownership Migration Plan

**Important note:**
The host/starter app is located in: D:\laragon\www\lexio-admin
You would need to do modifications in both applications (current bundle and host/starter app) to achieve some of the tasks here.

## Goal

Make `lexio/admin-bundle` the single owner of the reusable admin UI:

- admin Twig layouts, CRUD templates, form themes, fields, modals, and admin components;
- the Stimulus controllers required by those templates;
- the minimum structural CSS required for the admin UI to work;
- reusable admin translations, icons, and route/configuration contracts.

The starter/host application must retain only:

- application-specific admin pages and child templates;
- frontend/public-site templates and assets;
- branding tokens and documented admin theme overrides;
- the application asset entry point and build configuration;
- application-specific routes, entities, permissions, and business logic.

There must be no copied admin theme in both repositories. Host customization must use documented
Twig blocks, bundle configuration, CSS custom properties, or application-specific child templates.

## Target Architecture

| Concern | Bundle owns | Starter/host owns |
| --- | --- | --- |
| Admin Twig | `admin_base`, CRUD, fields, modals, admin components, form themes | Domain-specific child templates only |
| Stimulus | Controllers used by bundle Twig/PHP form types | Controllers used only by the public application |
| CSS | Bootstrap-compatible admin structure and component styles | Brand values, optional visual overrides, public-site styles |
| JavaScript dependencies | UX package metadata and peer dependency ranges | Dependency installation and application build entry |
| Routes | Generic bundle UI routes, or configurable route-name contracts | Domain-specific endpoints and configured implementations |
| Translations | Reusable admin messages in the bundle domain | Application-specific wording and optional overrides |
| Icons/images | Generic UI icons and a replaceable default admin mark | Product logo, favicon, manifest images, marketing imagery |
| Asset tooling | Source, compiled distributable assets, UX metadata | Encore/AssetMapper selection and final application build |

The bundle remains an opinionated Bootstrap 5 admin bundle. Do not split out a separate UI package
unless a second supported design system is actually introduced.

## Current-State Findings

The following files currently exist in both repositories.

### Shared admin templates to make bundle-only

| Template | Current difference | Resolution |
| --- | --- | --- |
| `admin/_modals/_association_modal_success.html.twig` | Identical | Delete host copy |
| `admin/_modals/links_search.html.twig` | Identical | Delete host copy |
| `admin/_modals/modal_success.html.twig` | Identical | Delete host copy |
| `admin/base_crud/details.html.twig` | Host injects its SEO partial | Add a bundle extension point; delete host copy |
| `admin/base_crud/form_tab.html.twig` | Host uses an unnamespaced flash include | Keep namespaced bundle include; delete host copy |
| `admin/base_crud/form.html.twig` | Host injects SEO and uses an unnamespaced field include | Add extension point and use bundle namespace; delete host copy |
| `admin/base_crud/listing.html.twig` | Host injects its SEO partial | Add a bundle extension point; delete host copy |
| `admin/base_crud/seo.html.twig` | Host uses an unnamespaced flash include | Keep namespaced bundle include; delete host copy |
| `admin/dashboard/index.html.twig` | Host adds SEO and host-only type comments | Keep contract types, add extension point; delete host copy |
| `admin/security_log/details.html.twig` | Host extends its copied CRUD template | Use the bundle namespace; delete host copy |
| `maker/admin_test.html.twig` | Content differs only mechanically | Keep bundle generator template; delete host copy |
| `maker/crud_controller.html.twig` | Identical | Delete host copy |
| `maker/filter.html.twig` | Identical | Delete host copy |

The host's application-specific templates such as `admin/blog/*`, `admin/contact/*`,
`admin/mail_template/*`, `admin/user/*`, `components/Admin/PublishBlog.html.twig`, and
the public-site component templates under `components/` may remain,
but they must extend explicit `@LexioAdmin/...` templates where applicable and must not recreate a
bundle layout. `PublishBlog` is paired with the host's `App\Entity\Blog` LiveComponent and remains
host-owned; it may include bundle-owned generic fragments such as the admin flash stream.
The public-site components are paired with host classes such as `App\Components\TopCategories`,
so their host templates are canonical and bundle duplicates must not be restored.

### Component ownership decisions

- `base.html.twig` is a public-site layout and should be host-owned. Audit bundle consumers, then
  remove it from the admin bundle or move any genuinely reusable frontend UI to a separate package.
- `flash.stream.html.twig` serves both public and admin pages. Give the bundle version an explicit
  admin path such as `@LexioAdmin/admin/flash.stream.html.twig`; retain a host version only for the
  public site.
- Public components `BlogSearchWidget`, `FeaturedBlogs`, `TopCategories`, `BlogRatingWidget`,
  `ImageSelector`, and `Img` are host-owned and have no bundle template copy.
- `ConfirmationModal` is the generic bundle-owned exception because bundle admin templates and
  `BaseCrudController` depend on it. The host duplicate is removed.
- Bundle admin image rendering uses the scoped `Admin:Img` component. The host keeps its public
  `Img` component and template.

## Delivery Strategy

Use two compatible releases rather than deleting files in both repositories at once:

1. Publish a bundle release containing the complete templates, extension points, controllers,
   styles, metadata, and compatibility documentation.
2. Upgrade the starter/host to that release and make it consume the bundle assets/templates.
3. Delete the now-unused host copies and local assets.
4. Remove temporary compatibility aliases only in a later major release if they are public API.

## Phase 0 — Baseline and Ownership Manifest

- [ ] Restore a working `make` command in the development environment; use the
  `quality-install` workflow if the repository still lacks the expected quality targets.
- [x] Run `make ci` in both repositories and record the baseline failures before migration.
- [ ] Capture browser screenshots for dashboard, listing, details, create/update form, modal
  association, CKEditor, image selector, datepicker, bulk actions, and mobile sidebar.
- [x] Build a machine-readable ownership manifest containing every bundle Twig template,
  Stimulus identifier, stylesheet, icon, translation key, and required route.
- [x] Scan Twig for `stimulus_controller()`, `stimulus_action()`, `stimulus_target()`, Bootstrap
  classes, route calls, translation domains, and unnamespaced includes.
- [x] Resolve missing or inconsistent controller identifiers, including `submit-confirm` and the
  underscore/hyphen spelling of `collapsable_sidebar`.
- [x] Add an ADR recording the single-package Bootstrap 5 decision and the host/bundle boundary.

Exit condition: every reusable admin UI dependency has one proposed owner and baseline behavior is
recorded.

## Phase 1 — Make Bundle Twig Canonical

- [x] Define the public Twig customization contract: stable block names, template variables,
  component props, form block prefixes, and which parts are semver-protected.
- [x] Ensure every reusable template references other bundle templates with `@LexioAdmin/...`.
- [x] Replace host-only SEO copies with a configuration-backed extension point, for example a
  nullable `lexio_admin.ui.seo_template`. The bundle layout should render it when configured.
- [x] Add configuration for replaceable favicon/admin logo templates or asset paths; remove
  hardcoded `build/images/*.svg` assumptions from the reusable layout.
- [x] Replace application translation domains such as `text` where the message belongs to the
  bundle. Store reusable messages in the bundle translation domain and allow normal Symfony
  translation overrides.
- [x] Make host route dependencies configurable or move generic endpoints into the bundle:
  CKEditor upload, internal-link search, image gallery, association modal, and autocomplete.
- [x] Rename/scope the bundle flash template so public and admin flash rendering cannot shadow one
  another.
- [ ] Add deprecation aliases for any template path that has already been published and cannot be
  changed immediately.
- [x] Add rendering tests for every canonical base template and extension point.
- [x] Add a test proving an application child template can override documented blocks without
  copying the parent template.

Exit condition: the bundle renders all generic admin screens without resolving a host copy of a
shared admin template.

## Phase 2 — Package the Stimulus Controllers

- [x] Add a bundle `assets/package.json` following Symfony UX package metadata conventions.
- [x] Add controller source under `assets/src/controllers/` and committed distributable builds
  under `assets/dist/controllers/`.
- [x] Register provider-specific CAPTCHA controllers with explicit metadata names: `turnstile` for
  Cloudflare Turnstile and `captcha` for Google reCAPTCHA Enterprise.
- [x] Move controllers in feature slices, including their helpers:
  - core UI: `modal`, `base-modal`, `open-base-modal`, `tooltip`, `navigate-turbo`, `autosubmit`;
  - CRUD/listing: `bulk-action`, `check-selection`, `sortable`, `clipboard`, `toggle-class`;
  - custom fields: `association-modal-type`, `add-autocomplete-item`, `links-search-field`,
    `ckeditor`, `input-image-selector`, `vanilla-datepicker`, `vanilla-datepicker-live`, `turnstile`,
    `captcha`;
  - media: `file-upload-form`, `image-gallery`, `image-selector`;
  - shell/feedback: `autocomplete-search`, `collapsable-sidebar`, `flash-message`;
  - optional components: `textarea-placeholders`.

  The public-site-only `onscroll` and `rating` controllers remain host-owned and are not part of
  the reusable admin package.
- [x] Move `flash.js`, CKEditor plugins, and datepicker locale helpers with the controllers that use
  them. The bundle ships a locale-neutral helper; product locale data remains host-provided.
- [x] Remove host-specific entity knowledge, URLs, translation text, and business rules from the
  controllers. Pass configuration through Stimulus values and keep authorization/server decisions
  on the server.
- [ ] Review CAPTCHA and upload flows separately for CSRF, authorization, file validation, endpoint
  configuration, and secret handling before moving them.
- [x] Declare third-party packages as peer dependencies rather than copying them: Stimulus, Turbo,
  Bootstrap, CKEditor, Tom Select, SortableJS, Vanilla Datepicker, Live Components, and `debounce`.
- [x] Configure eager/lazy loading per controller and document the compatibility policy for
  controller identifiers and values.
- [ ] Add controller unit tests and Turbo reconnect/disconnect tests. Add browser coverage for
  modals, CKEditor, datepicker, image selector, sorting, and bulk actions.

Exit condition: bundle templates have no dependency on controller source stored in the host.

## Phase 3 — Move Admin Styles Without Moving Branding

- [x] Split the host SCSS into three layers:
  1. Bootstrap 5 dependency/configuration;
  2. bundle structural/component styles;
  3. host brand tokens and optional overrides.
- [x] Move reusable admin layout, sidebar, navbar, tables, tabs, cards, breadcrumbs, modal gallery,
  notifications, flash messages, CKEditor, and datepicker styles into the bundle.
- [x] Provide two supported theme customization APIs:
  - stable `--lexio-admin-*` CSS custom properties for runtime overrides and consumers of the
    precompiled CSS;
  - a documented public Sass configuration module whose curated variables use `!default`, for
    Encore consumers that want to set theme values and compile the bundle source themselves.
- [x] Generate the default CSS custom-property values from the public Sass configuration so the two
  APIs do not drift. Colors, spacing, typography, radii, sidebar width, and logo dimensions should
  be available through both APIs where a compile-time value is meaningful.
- [x] Treat only the documented `$lexio-admin-*` Sass variables as semver-stable. Component
  partials and internal implementation variables remain private and must not be imported directly
  by a host application.
- [x] Keep the host's product logo, favicon, public-site SCSS, homepage styles, email CSS, manifest
  images, and marketing fonts outside the bundle.
- [x] Do not embed Bootstrap, Font Awesome, CKEditor, or other third-party source in the bundle.
  Declare supported versions and consume them as dependencies.
- [ ] Replace legacy font files and CDN-coupled icon markup with Symfony UX Icons or bundle-owned
  SVG icons. Provide an override point for product-specific icons.
- [x] Ship compiled `assets/dist/admin.css` for consumers without Sass and public source entries for
  applications that intentionally customize the build:
  - `@lexio/admin-bundle/styles/admin` includes the supported Bootstrap build plus bundle styles;
  - `@lexio/admin-bundle/styles/components` includes only bundle components for hosts that compile
    and configure Bootstrap separately.
- [x] Document an Encore host build such as:

  ```scss
  @use '@lexio/admin-bundle/styles/admin' with (
    $lexio-admin-primary: #4f46e5,
    $lexio-admin-font-family: (Inter, sans-serif),
    $lexio-admin-sidebar-width: 18rem,
    $lexio-admin-radius: 0.625rem
  );

  // Application-specific refinements load last.
  @use 'admin-theme';
  ```

- [x] Ensure each host selects exactly one base mode: compile the public Sass entry **or** load
  `assets/dist/admin.css`. Loading both is unsupported because it duplicates the base rules.
- [x] Ensure the host loads bundle CSS before its `admin-theme.css` overrides.
- [ ] Add visual regression checks at desktop and mobile widths, plus focus, reduced-motion,
  high-contrast, and keyboard navigation checks.

Exit condition: removing `assets/styles/admin/**` from the host does not change the baseline admin
layout except for approved fixes.

## Phase 4 — Support Encore and AssetMapper Consumers

- [x] Make Symfony UX package metadata the common controller-discovery contract.
- [x] For the current Encore host, add:
  - [x] `"@lexio/admin-bundle": "file:vendor/lexio/admin-bundle/assets"` to `package.json`;
  - [x] the bundle package and enabled controllers to `assets/controllers.json`;
  - [x] either the configurable public Sass entry or the precompiled bundle CSS before the host theme
    override in the `admin` entry.
- [x] Document the concrete host controller registration. The final controller list must match the
  bundle's `assets/package.json` metadata; for example:

  ```json
  {
    "controllers": {
      "@lexio/admin-bundle": {
        "association-modal-type": {
          "enabled": true,
          "fetch": "lazy"
        },
        "modal": {
          "enabled": true,
          "fetch": "eager"
        }
      }
    },
    "entrypoints": []
  }
  ```

- [x] Decide and test `eager` versus `lazy` for every packaged controller. Controllers used by the
  admin shell may be eager; feature-specific controllers should normally be lazy.
- [x] Keep only a small host `admin.js` entry for application assembly; it must not contain copied
  bundle controller or component source.
- [ ] For AssetMapper consumers, register the distributable asset path conditionally when the
  extension is installed and verify controller auto-import metadata.
- [ ] Do not make Encore mandatory in Twig. Provide `encore`, `asset_mapper`, and `manual` asset
  strategies, or a small bundle Twig asset renderer selected through `lexio_admin.ui` configuration.
- [ ] Add minimal integration fixtures that render and execute one admin page with Encore and one
  with AssetMapper.
- [ ] Consider a Symfony Flex recipe for initial package/controller configuration, while keeping
  upgrades non-destructive.

Exit condition: a consumer can install the bundle using either supported asset pipeline without
copying bundle assets into its source tree.

## Phase 5 — Migrate and Clean the Starter/Host

- [x] Upgrade the host to the local bundle package release that contains all compatibility hooks and assets.
- [x] Change domain-specific templates to extend explicit bundle parents:
  - `admin/blog/blog_listing.html.twig` → `@LexioAdmin/admin/base_crud/listing.html.twig`;
  - `admin/blog/update.html.twig` → `@LexioAdmin/admin/base_crud/form.html.twig`;
  - `admin/user/listing.html.twig` → `@LexioAdmin/admin/base_crud/listing.html.twig`;
  - any remaining generic CRUD child → the corresponding `@LexioAdmin/...` parent.
- [x] Change controller render calls for generic bundle screens to explicit `@LexioAdmin/...`
  paths. The dashboard currently requires this change; modal/security-log calls must be rechecked.
- [x] Configure the host SEO partial, logo/favicon, route contracts, translations, and brand CSS
  through the new extension points.
- [x] Enable every required bundle controller in `assets/controllers.json`, preserve existing
  Symfony UX package entries, and verify the resulting `data-controller` identifiers against the
  bundle Twig templates.
- [x] Remove migrated controller files, helpers, CKEditor plugins, and admin styles from the host.
- [x] Delete all host copies listed in "Shared admin templates to make bundle-only".
- [x] Remove host maker templates now provided by the bundle.
- [x] Remove obsolete imports and npm packages that are no longer directly used by the host; all remaining third-party packages are still required by the host entry, public site, or bundle peer contract.
- [x] Rebuild assets and verify there are no stale compiled files masking missing source imports.
- [x] Run a duplicate-path scan. Any remaining overlap must have an explicit ownership decision;
  accidental Twig shadowing is a release blocker.

Exit condition: the host contains no generic admin layout/theme copy and all admin behavior resolves
from the installed bundle.

## Phase 6 — Prevent Regression

- [x] Add a bundle test that compiles every Twig template with strict variables where practical.
- [x] Add dependency-contract tests mapping Twig controller identifiers to package metadata.
- [x] Add tests mapping configured routes and translation keys used by templates.
- [x] Add a host CI check that compares application template paths with bundle template paths and
  fails for forbidden shared-admin overlaps.
- [x] Maintain a small allowlist only for deliberate application-specific child templates; do not
  allow full copied parents.
- [x] Add a CI check that host-local controllers do not reuse bundle controller identifiers.
- [ ] Run `make ci` in the bundle and starter/host on every coordinated migration change.
- [x] Document template blocks, CSS variables, Stimulus values, asset setup, and upgrade steps.
- [x] Update the starter application documentation so new projects never begin by copying vendor
  templates or assets.

## Phase 7 — Public-Site Template Cleanup

This phase is separate from the admin migration but is necessary to finish the ownership model.

- [x] Inventory bundle PHP classes that render `base.html.twig` or non-admin components. No bundle
  PHP class renders the public layout or public-site components; the remaining public template
  copies were `base.html.twig` and `flash.stream.html.twig`.
- [x] Move application-specific blog, rating, search, category, header/footer, SEO, and marketing UI
  to the host. These application-specific templates and components are already host-owned.
- [x] Keep only components that are part of the documented admin product surface in the bundle;
  move them under `components/Admin` where appropriate.
- [x] Remove same-path public templates from one repository so every template has one canonical
  owner. The bundle copies of `base.html.twig` and `flash.stream.html.twig` were removed; the
  host copies are canonical.
- [x] Confirm that removing public templates does not break bundle services or published contracts;
  the bundle has no internal consumers of those paths, and the host public base now includes its
  local flash stream. The removal is a breaking change for consumers of the old public bundle paths
  and must be included in major-release migration notes.

## Release and Rollback Plan

- The current migration state includes the Phase 7 removal of the bundle public layout and
  unscoped public flash template, plus removal of public-only controllers from the bundle package.
  Those are breaking changes for consumers of the published paths/controllers and require a major
  release. The additive admin ownership work may be released as a minor version only when its
  published compatibility paths are retained.
- [x] Document the release classification, coordinated bundle/host sequence, and rollback
  procedure in `docs/admin-ui-release-and-rollback.md`.
- [ ] Release additive bundle work as a minor version when compatibility paths are retained, or
  release the current Phase 7 state as a major version with migration notes.
- [ ] Pin the host to that release before deleting any host source.
- [ ] Make host cleanup a separate commit so it can be reverted independently.
- [ ] Keep old host files recoverable through Git; do not delete compiled assets until the new build
  succeeds.
- [ ] If browser verification fails, restore only the affected host feature slice while keeping
  already-verified slices on bundle assets.
- [ ] Remove deprecations only in a planned major bundle release.

## Definition of Done

- [ ] No generic admin Twig template exists in both the bundle and starter/host.
- [ ] All application-specific admin templates extend `@LexioAdmin/...` parents.
- [ ] No bundle Twig template requires a host-local Stimulus controller or structural stylesheet.
- [ ] No reusable bundle template hardcodes a host-only route, product asset, entity class, or
  application translation domain.
- [ ] Host branding works through CSS variables/configuration without copying an admin template.
- [ ] Encore and AssetMapper integration fixtures pass.
- [ ] Bundle and host `make ci` pass.
- [ ] Browser and accessibility checks pass for the critical admin workflows.
- [x] Upgrade and rollback documentation is complete; execution remains pending the tagged bundle
  release, pinned host dependency, successful builds, and browser verification.
