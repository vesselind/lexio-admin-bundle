# Architecture: Synchronize Translation Packages Between Environments

## Existing Patterns Observed
- `LexioAdminBundle` uses `AbstractBundle::configure()` and `loadExtension()`, with services autowired from `config/services.yaml` and explicit aliases for public contracts.
- Translation management already has a configurable host translation directory, a safe flat-YAML catalog, an extendable bundle admin controller, and a concrete host controller that owns routing and authorization.
- The admin base template exposes a right-aligned `page_actions` block suitable for Send and Receive forms.
- The host imports ordinary controllers with localized route prefixes, so the synchronization API needs a separate unlocalized route import.
- Symfony HttpClient and `ext-zip` are already dependencies; bundle console commands are service-tagged rather than relying on host application commands.

## Technical Design

### Components
- `translation_management.synchronization` configuration: `enabled` (default `false`), `deployed_app_url`, `api_path`, `auth_salt`, signature TTL, HTTP timeout, package size/file limits, and optional Basic Auth username/password. `APP_SECRET` is consumed through `%kernel.secret%`; credentials remain environment-backed values referenced from bundle YAML.
- `TranslationSynchronizationOptions`: internal immutable options object grouping validated synchronization settings. A missing remote URL is allowed for a receive-only API environment but causes outbound operations to fail safely.
- `FlatTranslationDocumentCodec`: extracts the flat filename/key/value validation and YAML parsing/dumping rules currently embedded in `YamlTranslationCatalog`, so admin editing and package synchronization enforce one format consistently.
- `TranslationPackageManager`: exports supported root-level `*.yaml` files into a ZIP and imports an in-memory ZIP after validating every archive entry and YAML document. Import uses `local + incoming` key union with incoming values winning, creates missing files, never removes local files/keys, stages all merged documents before writing, and atomically replaces each affected file.
- `TranslationPackageMergeResult`: immutable public result DTO containing created/updated file and inserted/updated/unchanged key counts.
- `TranslationPackageRequestAuthenticator`: signs and verifies `timestamp + method + request path + SHA-256(body)` with HMAC-SHA256 using a key derived from `%kernel.secret%` and the configured salt. Headers carry timestamp and signature; `hash_equals()` and a configurable short TTL reject invalid or stale requests.
- `TranslationPackageHttpClient`: builds `DEPLOYED_APP_URL + api_path + operation`, applies request timeout and optional Symfony HttpClient `auth_basic`, signs upload/download requests, enforces response and package limits, and converts transport/non-success responses into bundle exceptions.
- `TranslationPackageSynchronizer`: high-level public service used by commands and admin actions. `upload()` exports locally and sends to the deployed upload endpoint; `download()` fetches the deployed archive and merges it locally.
- `UploadTranslationsCommand` and `DownloadTranslationsCommand`: bundle commands named `lexio:translations:upload` and `lexio:translations:download`, registered through service configuration, using `SymfonyStyle`, clear summaries, and non-zero failure exit codes.
- Abstract bundle `TranslationPackageController`: thin binary API boundary with inherited GET `/download` and POST `/upload` actions. It authenticates before export/import, returns ZIP with `no-store` headers for download, merge counts as JSON for upload, and RFC 7807 responses for invalid authentication/package input.
- Host `App\Controller\Api\TranslationPackageController`: concrete route owner with `/api/translations` prefix. Host routing excludes `Controller/Api` from the localized controller import and imports it separately without locale prefixes. The API path is explicitly `PUBLIC_ACCESS` because HMAC authentication is enforced at the controller boundary.
- Existing admin `TranslationController`: receives POST Send and Receive actions protected by distinct CSRF tokens. The template renders translated forms in `page_actions` only when synchronization is enabled and `kernel.environment !== prod`; the controller rejects direct UI action requests in `prod` and preserves file/search/page state after redirect.
- Host integration: configure synchronization in `config/packages/lexio_admin.yaml`; add `DEPLOYED_APP_URL`, salt, and optional Basic Auth environment variables; add the concrete API controller, unlocalized route import, security rule, and Bulgarian/English admin messages.

### Implementation Order
1. **Dependencies and config** — add direct Console/Clock dependencies if absent; extend bundle configuration, validate paired Basic Auth credentials, create parameters/options, and wire services/aliases/command tags.
2. **Public Contract** — add `TranslationPackageSynchronizerInterface`, `TranslationPackageMergeResult`, and `TranslationSynchronizationException` without exposing Symfony HTTP or ZIP infrastructure types.
3. **YAML/package implementation** — extract the shared flat-document codec, adapt `YamlTranslationCatalog`, implement safe ZIP export/import, limits, staging, non-destructive merge, and atomic writes.
4. **Authentication and HTTP client** — implement deterministic HMAC signing/verification with an injectable clock, fixed configured destination URL, optional `auth_basic`, timeout/size enforcement, and mocked-client tests.
5. **Commands** — implement and explicitly register upload/download commands with success/failure coverage.
6. **API boundary** — add the abstract bundle API controller, binary/JSON/RFC 7807 responses, and host unlocalized concrete controller/routing/security integration.
7. **Admin boundary** — add non-production Send/Receive actions, right-aligned translated buttons, CSRF checks, flash messages, and redirect-state preservation.
8. **Verification/docs** — add unit, integration, and host functional coverage; document environment/config examples; run `make ci` after every implementation layer.

### Interfaces / Contracts
- `TranslationPackageSynchronizerInterface::isEnabled(): bool`
- `TranslationPackageSynchronizerInterface::canUseAdminActions(): bool`
- `TranslationPackageSynchronizerInterface::upload(): TranslationPackageMergeResult`
- `TranslationPackageSynchronizerInterface::download(): TranslationPackageMergeResult`
- `TranslationPackageMergeResult` is `final readonly` and exposes file/key merge counters only.
- `TranslationSynchronizationException` is the safe public failure type for package, authentication, configuration, and transport failures.

## Applicable Rules
The developer MUST read these rules before implementing:
- `.agents/rules/architecture.md` — options object, bundle layer order, and public contract boundaries.
- `.agents/rules/symfony-bundle.md` — `AbstractBundle` configuration, explicit service/command registration, feature enablement, and semver-safe contracts.
- `.agents/rules/coding-standards.md` — strict types, final/readonly services, constructor injection, and thin controllers/commands.
- `.agents/rules/dto.md` — immutable result/options objects and boundary-safe data.
- `.agents/rules/api.md` — binary API boundary, content negotiation, status codes, and RFC 7807 errors.
- `.agents/rules/error-handling.md` — safe exception mapping without secret/path leakage.
- `.agents/rules/security.md` — HMAC verification, timing-safe comparison, CSRF, replay window, ZIP traversal/bomb limits, SSRF prevention, and secret handling.
- `.agents/rules/http-client.md` — scoped destination, timeout, optional Basic Auth, response limits, and `MockHttpClient` tests.
- `.agents/rules/console-commands.md` — SymfonyStyle, command naming, exit codes, and `CommandTester` coverage.
- `.agents/rules/i18n.md` — translation-key-only buttons and flash messages.
- `.agents/rules/twig.md` — escaped, right-aligned page actions and production visibility.
- `.agents/rules/testing.md` — package/auth/client/command/controller success and failure coverage.
- `.agents/rules/quality-pipeline.md` — `make ci` as the only final verification entry point.

## Risks and Trade-offs
- `APP_SECRET` must match between environments for signatures to interoperate. Sharing it with a developer machine broadens its exposure; the configured salt derives a feature-specific HMAC key but does not remove that operational requirement.
- Timestamp-only replay protection permits replay inside the accepted TTL. A short TTL is the chosen simple model; nonce storage can be added later if strict one-time requests are required.
- Re-dumping merged files normalizes quoting and discards comments in affected files. This is compatible with the established flat one-record-per-line convention and does not alter untouched files.
- Filesystem updates cannot be perfectly transactional across multiple files. Full package validation and staging occur before writes, and every file replacement is atomic, minimizing but not eliminating a mid-commit partial merge after an I/O failure.
- ZIP archives are attacker-controlled input. Root-only safe filenames, duplicate rejection, compressed/uncompressed limits, file-count limits, and validation-before-write are mandatory.
- The API must bypass session login while remaining inaccessible without HMAC. The host route/security integration and functional tests must verify that custom authentication cannot be skipped.
- The configured deployed URL is an SSRF boundary. Only configured HTTP(S) origins are used, redirects are disabled or restricted, and commands accept no arbitrary URL argument.
