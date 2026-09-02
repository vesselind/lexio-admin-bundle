# ADR 0001: Keep the Admin UI in the Bootstrap Bundle

- Status: accepted
- Date: 2026-08-31

## Decision

`lexio/admin-bundle` remains the single package owner of the reusable admin UI and stays based on
Bootstrap 5. A separate UI package is not introduced while there is only one supported design
system.

The bundle owns generic admin Twig templates, form themes, admin components, structural styles,
reusable translations, icons, and the configuration contracts required by those templates. The
starter application owns product branding, public-site UI, domain-specific admin child templates,
application assets/build configuration, routes, entities, and business logic.

## Consequences

- Host templates extend explicit `@LexioAdmin/...` parents and use documented blocks.
- Host asset pipelines install and assemble bundle assets; the bundle does not require Encore.
- Host-specific SEO, logos, favicons, and route names are configuration or child-template concerns.
- Template aliases are retained during migration and removed only in a planned major release.
- Bootstrap-compatible structural CSS can move to the bundle without moving host brand tokens.

## Rejected alternative

Splitting the admin UI into a second package would add dependency and release coordination without
supporting a second design system. Revisit this decision only when another design system must be
supported independently.
