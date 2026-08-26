# Architecture: Edit Translation Values from the Admin

## Existing Patterns Observed
- `LexioAdminBundle` uses Symfony `AbstractBundle::configure()` and `loadExtension()` for bundle configuration and service parameters.
- Bundle services are autowired from `config/services.yaml`; controllers are exposed separately with `controller.service_arguments`.
- Host admin controllers use attribute routes and `IsGranted`, and the starter app owns the concrete controller while reusable behavior lives in the bundle.
- Twig templates are exposed through the `@LexioAdmin` namespace and admin pages extend `@LexioAdmin/admin_base.html.twig`.
- The existing `Admin:InlineEdit` component is entity/Doctrine-specific, so translation-file editing needs a file-oriented boundary rather than reusing its entity assumptions.

## Technical Design

### Components
- `translation_management` bundle configuration: an `enabled` flag and a configurable translation directory, defaulting to the host project’s `translations/` directory.
- `TranslationCatalogInterface`: public bundle contract for listing translation files, reading flat entries, checking availability, and updating one entry.
- Immutable `TranslationFile` and `TranslationEntry` output objects: expose only domain/locale/filename and key/value data to controllers and templates.
- `YamlTranslationCatalog`: internal implementation that discovers only safe `snake_case.locale.yaml` files, validates flat string mappings, serializes values with Symfony Yaml, and atomically replaces the target file.
- `UpdateTranslationInput`: validated HTTP boundary object for domain, locale, key, and value; empty values remain valid.
- Abstract `LexioAdminBundleControllerAdminTranslationController`: reusable GET listing and POST update actions with method route attributes. The host application subclasses it, adds its class route prefix and authorization attribute, and therefore owns the concrete route controller.
- Bundle Twig template: file selector plus one inline form/input per translation entry, using translation keys for all UI copy.

### Implementation Order
1. **Config** — add translation-management enablement and translation-directory parameters in `LexioAdminBundle`; wire the catalog service and its public interface alias.
2. **Public Contract** — add `TranslationCatalogInterface`, `TranslationFile`, and `TranslationEntry` under `src/Contract/Translation`.
3. **Implementation** — add the YAML catalog service, safe input exception, and validated update input; add unit tests for discovery, flat-file validation, safe serialization, atomic update behavior, and path/key rejection.
4. **Controller/UI** — add the extendable bundle admin controller and namespaced Twig template; add host `App\Controller\Admin\TranslationController` with `/admin/translations` and `ROLE_ADMIN`.
5. **Host integration/tests** — add admin translation keys, route ownership coverage, and host-level route/container checks.

## Interfaces / Contracts
- `TranslationCatalogInterface::isEnabled(): bool`
- `TranslationCatalogInterface::listFiles(): list<TranslationFile>`
- `TranslationCatalogInterface::getEntries(string $domain, string $locale): list<TranslationEntry>`
- `TranslationCatalogInterface::update(string $domain, string $locale, string $key, string $value): void`
- `TranslationFile` and `TranslationEntry` are `final readonly` output objects with public constructor-promoted properties.

## Applicable Rules
The developer MUST read these rules before implementing:
- `.agents/rules/architecture.md` — bundle layer order, config-driven services, and public contracts.
- `.agents/rules/symfony-bundle.md` — AbstractBundle configuration, service registration, extensionability, and translation conventions.
- `.agents/rules/coding-standards.md` — strict types, final/readonly classes, constructor injection, and boundary discipline.
- `.agents/rules/dto.md` — validated HTTP input object and immutable output objects.
- `.agents/rules/i18n.md` — translation-key-only UI copy and safe boundary translation.
- `.agents/rules/security.md` — authorization, CSRF, path traversal, and safe error handling.
- `.agents/rules/twig.md` — escaped output and admin template conventions.
- `.agents/rules/testing.md` — unit and functional coverage for success/error paths.
- `.agents/rules/quality-pipeline.md` — `make ci` as the verification entry point.

## Risks and Trade-offs
- Re-dumping the valid flat mapping can normalize YAML quoting/formatting and remove comments; this is accepted because the established convention is one flat record per row and comment preservation is out of scope.
- Last-write-wins concurrency is acceptable for this CMS-sized file editor; atomic replacement prevents partial files from being observed.
- A bundle controller cannot own the host route collection automatically, so the host subclass is required and is the route owner by design.
- Only `.yaml` files matching the established naming convention are exposed; other translation resource formats remain untouched.
