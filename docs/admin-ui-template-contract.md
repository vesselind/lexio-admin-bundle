# Admin UI Twig Contract

The bundle owns the reusable admin layout and generic admin templates. Applications customize
those templates by extending a namespaced bundle parent:

```twig
{% extends '@LexioAdmin/admin/base_crud/listing.html.twig' %}
```

## Stable layout blocks

The following blocks are semver-protected extension points on
`@LexioAdmin/admin_base.html.twig`:

- `title`, `title_suffix`, `favicon`, `stylesheets`, `javascripts`, `responsive_elements`,
  and `jsonld` for document head customization;
- `sidebar`, `header`, `header_logo`, `header_search`, `header_notifications`, `user_avatar`,
  and `user_menu_items` for the admin shell;
- `main`, `breadcrumbs`, `heading_parent`, `heading`, `page_actions`, `content`, and
  `below_content` for page composition.

Applications must override these blocks instead of copying the parent template. The bundle layout
renders the configured asset paths when present.

## Configuration global

Bundle templates may use the read-only `lexio_admin_ui` Twig global:

- `translation_domain` — reusable admin message domain, default `LexioAdminBundle`;
- `favicon_asset`, `admin_logo_asset`, and `admin_logo_alt`;
- `title_translation_key` and `title_translation_domain`;
- `routes.*` for shell, notification, flash, modal, upload, image, and file route contracts.

Route names and asset paths are application configuration. No reusable bundle template may assume
the host's `build/images` directory or use an unnamespaced include for admin UI.

## Components and forms

Twig component props are public contracts. Existing component names and props remain stable, including
`Admin:OpenBaseModalBtn`, `Admin:BaseModal`, `Admin:NotificationBell`, `Admin:BulkActions`,
`Admin:SideMenu`, `Admin:Img`, and `Admin:InputImageSelector`. `ConfirmationModal` is the generic bundle-owned component
outside the `Admin:` namespace. It supports `confirmUrl` for session-backed redirects and the
optional `dispatchEventName` prop for a host live component that handles confirmation locally.

Custom form block prefixes are stable: `association_modal_widget`, `input_image_selector_widget`, and
`ck_editor_label`.
Applications may override those blocks through their form theme; ordinary Symfony form blocks remain
owned by the host's selected base form theme.

`CKEditorType` and `InputImageSelectorType` consume the configured upload/gallery routes. Association
forms provide their create URL through the existing `visit_url` option, so the bundle does not need
to know an application entity route.

The packaged controllers receive request URLs through Stimulus values. In particular, `ckeditor` uses
`uploadUrl`, `image-gallery` uses `uploadUrl`, `currentUrl`, `currentFrame`, and `flashUrl`, while
`sortable` and `textarea-placeholders` use `flashUrl` when they render a server-generated flash
stream.

## Compatibility policy

Published bundle admin template names and custom form block prefixes are semver-protected. Public
site layout and flash templates are host-owned; the bundle does not publish `base.html.twig` or the
unscoped `flash.stream.html.twig` path. Admin templates must use
`@LexioAdmin/admin/flash.stream.html.twig`. Consumers upgrading from a release that provided the
old public bundle paths must move those templates into the application as part of the major-release
migration.
