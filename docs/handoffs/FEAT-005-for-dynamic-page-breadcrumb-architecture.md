# Architecture: `FrontBreadcrumbs::forDynamicPage()` — dynamic page breadcrumb

## Existing Patterns Observed
- `FrontBreadcrumbs` is an existing fluent service with constructor-promoted `private readonly` dependencies. `forHome()` and `addItem()` return `static`; `addItem()` translates route labels through the `breadcrumbs` domain and generates a URL.
- `AdminBreadcrumbs::forPage()` establishes the existing current-page pattern: append the page title directly to `Breadcrumbs` without generating a route URL.
- `BaseController::breadcrumbs()` resolves the `front_breadcrumbs` service-subscriber entry, while `config/services.yaml` autowires the `Lexio\AdminBundle\` source prototype. No dedicated `FrontBreadcrumbs` service definition is required.
- `PageManagerInterface::getPageObject(string $pageClass, ?string $locale = null): ?object` is the locale-aware retrieval boundary. `BasePage::getTitle(): ?string` is the page-title contract; neither contract needs to change.
- Huluti's `Breadcrumbs::addItem($text, $url, $translationParameters, $translate)` stores a `SingleBreadcrumb`. Passing `addItem($title, '', [], false)` produces an unlinked, untranslated item.
- Unit tests use strict types, `PHPUnit\Framework\TestCase`, `createMock()`/`createStub()`, `self::assert*()` assertions, and small private fixture helpers. The existing functional page test uses a minimal kernel and an explicit `PageManagerInterface` alias when container wiring must be exercised.

## Technical Design

### Components
- `FrontBreadcrumbs`: add `forDynamicPage(string $pageClass, ?string $locale = null): static`, documented with `@param class-string<\\Lexio\\AdminBundle\\Page\\BasePage>`. Resolve `$locale ?? $this->requestStack->getCurrentRequest()?->getLocale()`, pass the result to `PageManagerInterface::getPageObject()`, and return `$this` unchanged when the manager returns no object, a non-`BasePage` object, or a null/blank title. For a non-blank title, call `$this->breadcrumbs->addItem($title, '', [], false)` and return `$this`. Preserve the original title text after checking `trim($title) === ''`; do not translate it.
- `FrontBreadcrumbs` dependencies: inject `PageManagerInterface $pageManager` and `RequestStack $requestStack` by constructor promotion. Keep the existing router, breadcrumbs model, translator, and configured home-route behavior unchanged. `RequestStack` is safe outside an HTTP request; a missing current request leaves `null` for `PageManager`'s configured default-locale fallback.
- Service wiring: make no `config/services.yaml` change. The existing source prototype autowires `PageManagerInterface` through the host/bundle alias and `RequestStack` through Symfony. Verify the controller service-subscriber key `front_breadcrumbs` still resolves with a minimal container test or host-equivalent kernel configuration.
- `FrontBreadcrumbsTest`: add the requested unit test class under `tests/Unit/AdminCore/Breadcrumbs/`. Use a real `Huluti\\BreadcrumbsBundle\\Model\\Breadcrumbs` and inspect `SingleBreadcrumb` fields (`text`, `url`, `translate`) while mocking the page manager, request stack/request, router, and translator. Cover a `BasePage` title, explicit locale, request locale, no request, missing page, non-`BasePage` result, null/empty/whitespace title, and `forHome()->forDynamicPage()` chaining.

### Implementation Order
1. **Config/services verification** — confirm no new service definition or alias is needed; ensure the existing autowired `front_breadcrumbs` service can resolve both new constructor dependencies when `PageManagerInterface` is available.
2. **Public concrete API** — add the documented `forDynamicPage(string $pageClass, ?string $locale = null): static` method to `FrontBreadcrumbs`; do not introduce `FrontBreadcrumbsInterface` or change `PageManagerInterface`/`BasePage`.
3. **Implementation** — add locale selection, page/type/title guards, and the direct untranslated `Breadcrumbs` insertion while preserving `forHome()` and `addItem()` byte-for-byte in behavior.
4. **Tests** — create the focused unit suite and, where practical, assert the `front_breadcrumbs` service wiring with the existing minimal-kernel/container convention.
5. **Quality and release handoff** — run `make ci`; bump the revision on commit and mention the new public API and host usage `forHome()->forDynamicPage(SomePage::class)` in the commit message.

### Interfaces / Contracts
- `FrontBreadcrumbs::forDynamicPage(string $pageClass, ?string $locale = null): static` — new public concrete API; `$locale` explicitly overrides `RequestStack` resolution, and `null` with no current request is passed through to `PageManagerInterface`.
- `PageManagerInterface::getPageObject(string $pageClass, ?string $locale = null): ?object` — existing contract, used unchanged.
- `BasePage::getTitle(): ?string` — existing title contract, used after an `instanceof BasePage` guard.
- `FrontBreadcrumbsInterface` — deliberately not added. Introducing a new contract would expand the semver surface without being required by this one method.

## Applicable Rules
The developer MUST read these rules before implementing:
- `../rules/architecture.md` — preserve the bundle's existing service boundary and layer order; avoid adding configuration or a contract when the prototype already provides wiring.
- `../rules/symfony-bundle.md` — public API and semver discipline, constructor-injected services, and the documented exception to the usual final/read-only service default for this existing extension point.
- `../rules/coding-standards.md` — `declare(strict_types=1)`, typed constructor injection, interface-driven dependency use, and whitespace-safe title handling without side effects.
- `../rules/testing.md` — focused unit coverage for fluent behavior, locale forwarding, no-op edge cases, and real model assertions; use the Makefile test entry point.
- `../rules/i18n.md` — retain the `breadcrumbs` translation domain for existing route-based methods, but mark CMS titles as already-localized content with `$translate = false`; no translation key is introduced.
- `../rules/observability.md` — the selected missing-page/title policy is a documented silent no-op because absent optional CMS content is an expected condition, not an operational failure; do not add noisy logging.
- `../rules/quality-pipeline.md` — validate through `make ci` and do not invoke PHPUnit, PHPStan, Composer, or other tools directly.

## Risks and Trade-offs
- **Inheritance BC:** Keep `FrontBreadcrumbs` non-final and non-readonly. Host applications may subclass the existing class; converting it to `final readonly` would be a semver-breaking change and would contradict the established extension surface. Retain property-level readonly dependencies where compatible.
- **Constructor BC:** Adding two required constructor dependencies can break host code that manually instantiates `FrontBreadcrumbs`; the supported bundle path is autowired service resolution. Do not hide missing dependencies behind a service locator or nullable fallback. Call out the constructor change in release notes/upgrade guidance and verify the `front_breadcrumbs` service path.
- **Page-manager contract looseness:** Because the manager returns `?object`, guard with `instanceof BasePage` before calling `getTitle()`. A non-conforming object is silently skipped rather than causing a runtime error; the class-string PHPDoc communicates the intended caller contract.
- **Locale precedence:** Explicit `$locale` must win over the request locale. Otherwise use `RequestStack::getCurrentRequest()?->getLocale()` at call time, not construction time, and pass `null` when no request exists so the page manager retains default-locale responsibility.
- **Content versus translation:** Translating the title would treat localized CMS content as a translation key and could corrupt or replace it. The fourth `false` argument is required; tests must assert `SingleBreadcrumb::$translate === false` and an empty URL.
- **Empty content policy:** Null, empty, and whitespace-only titles are silently skipped and preserve fluent chaining. This avoids invalid breadcrumb entries without introducing logging or a new user-facing error path.
- **API scope:** Do not add a route or linked variant. The dynamic page is the current breadcrumb, and an unlinked item matches `AdminBreadcrumbs::forPage()` while keeping the public API minimal.
