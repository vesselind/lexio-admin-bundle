# Translations functionality



## Context

This is a bundle called Lexio Admin Bundle. Its purpose is to provide easy admin and CMS functionalities for fast creation of admin panels of websites and systems.

This bundle is under active development. For a host application it uses the following project: `D:\laragon\www\lexio-admin`. The latter one serves as host application and it is a starter app which holds many entities and logic, that is considered "a minimum" when starting to develop new website or web system.

For handling translations we use the built-in symfony provided translation system with YAML files located in the `/translations` directory of the starter app. The established convention is:

- there is a single file per translation domain and per locale; the name of the translation domain follows this pattern: `snake_case_of_domain.locale.yaml`.

  - Example: admin.bg.yaml, admin.en.yaml, form.bg.yaml, form.en.yaml, etc...

- The files contains a single record per row: translation key - translation values, using snake case and dot notation when composing translation keys names. Nesting of the translation keys one below another is not allowed. Examples:

  - Good:

    ```yaml
    label.tile: 'Заглавие'
    label.info: 'Инфо'
    ```

  - Bad:

    ```yaml
    label
    	.title: 'Заглавие'
    	.info: 'Инфо'
    ```

    



## Our goal

We need to be able to edit and update translation values, that are stored in the yaml files mentioned above. We need to have admin panel listing all translation keys and values per translation domain with inline input field for editing them (inline input editing is supported already in project via existing live twig component field). 

When editing translation value, the respective translation record in respective translation file should be updated accordingly.

When 



When developing a website project we may add many translation keys and values in the yaml translaiton files. Those files, for now, are git included. When we move to maintenance in production mode this can produce problems. When a user in the admin panel edit some translation value, this will update the yaml file. Next time, when we try to commit some changes and translation values, a git conflict will arise. Furthermore, we wont be able to have the actual version of translation locally when developing. Therefore, for translations we need a **single source of truth.**


## Implemented administration flow

The bundle provides a configurable `TranslationCatalogInterface` backed by the host application's flat YAML files. The default directory is `%kernel.project_dir%/translations` and the feature can be disabled with:

```yaml
lexio_admin:
    translation_management:
        enabled: false
```

The host application exposes the bundle controller at `/admin/translations` and protects it with its existing admin role. Only files matching `snake_case.locale.yaml` and containing flat string records with dot-notation keys are shown. Each row submits an inline value form; the catalog validates the file and atomically replaces only the selected record while preserving neighboring lines.

## Translation package synchronization

Synchronization is opt-in and exchanges root-level flat `*.yaml` resources as a ZIP package. Imports are non-destructive: incoming files and keys are created, matching values are updated, and receiver-only files and keys remain unchanged.

Configure each host application with environment-backed values:

```yaml
# config/packages/lexio_admin.yaml
lexio_admin:
    translation_management:
        enabled: true
        translation_directory: '%kernel.project_dir%/translations'
        synchronization:
            deployed_app_url: '%env(DEPLOYED_APP_URL)%'
            api_path: '/api/translations'
```

```dotenv
DEPLOYED_APP_URL=https://deployed.example.com
```

The communicating environments must use the same `APP_SECRET`. The bundle derives the synchronization key from that existing Symfony secret; no provider or extra translation-synchronization salt is required. Optional HTTP Basic Auth remains available when the deployed application requires it. Redirects are disabled for outbound synchronization.

The host owns a concrete controller extending `Lexio\AdminBundle\Controller\Api\TranslationPackageController` with the configured `/api/translations` prefix. Import that controller outside locale-prefixed route imports and allow `PUBLIC_ACCESS` for the exact API path; every API request is independently authenticated by its short-lived HMAC signature.

Run synchronization from the local host application with:

```text
translation:push
translation:pull
```

The legacy aliases `lexio:translations:upload` and `lexio:translations:download` remain available.

The translation admin page also shows Send and Receive actions when synchronization is enabled outside the `prod` environment. These actions are intentionally hidden and rejected in production; the signed API remains available there when enabled.

## Translation scanning

Use `translations:scan` to find potentially untranslated host-template text and translation placeholders:

```text
translations:scan
translations:scan templates/checkout
translations:scan translations/admin.bg.yaml
translations:scan --format=json --fail-on-findings
```

Without a path, the command scans `%kernel.project_dir%/templates` and the configured `translation_management.translation_directory`. A supplied relative path is resolved from the host project root. Twig directories are scanned recursively; an explicit YAML file must be a managed flat `<domain>.<locale>.yaml` resource in the configured translation directory.

The command reports hardcoded Twig text and visible/accessibility attributes such as `alt`, `title`, `placeholder`, and `aria-label`. It ignores Twig comments, Twig expressions, `{% trans %}` blocks, scripts, styles, HTML comments, and regions wrapped in:

```twig
{# translation-scan-ignore-start #}
<span>Intentional brand name</span>
{# translation-scan-ignore-end #}
```

A managed YAML value beginning with `__` is reported as a `placeholder` finding in every locale:

```yaml
admin.dashboard.title: __admin.dashboard.title
```

`--min-length` applies only to Twig text candidates. Use repeatable `--exclude=directory-name` to omit template directories. Findings return a success status by default; add `--fail-on-findings` for CI. Invalid or non-flat managed YAML is always reported as a scan failure.
