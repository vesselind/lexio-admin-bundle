# Feature: `FrontBreadcrumbs::forDynamicPage()` — dynamic page breadcrumb

## Context
**Issue Tracking:** None

CMS-driven front pages currently require host applications to hardcode breadcrumb labels. This duplicates page content, can drift from seeded data, and does not follow the request locale. `FrontBreadcrumbs` needs a chainable method that uses a page object's localized title as the current, unlinked breadcrumb item.

## User Stories
- As a host application developer, I want to call `forHome()->forDynamicPage(SomePage::class)` so that CMS page breadcrumbs use the page's localized title without duplicated labels.
- As a site visitor, I want the current breadcrumb title to match the requested locale so that navigation reflects the page content I am viewing.
- As a host application developer, I want missing page data to be skipped safely so that breadcrumb rendering and fluent chaining continue to work.

## Acceptance Criteria (Gherkin)
Given `PageManagerInterface` returns a `BasePage` with a non-empty title
When `forDynamicPage(SomePage::class)` is called
Then the title is added as the current breadcrumb item with no URL
And the title is not passed through the `breadcrumbs` translator
And the method returns the same `FrontBreadcrumbs` instance

Given an explicit locale is supplied to `forDynamicPage(SomePage::class, 'en')`
When the page is resolved
Then `getPageObject(SomePage::class, 'en')` receives that locale

Given no explicit locale is supplied and the current request locale is `en`
When `forDynamicPage(SomePage::class)` resolves the page
Then `getPageObject(SomePage::class, 'en')` receives the request locale

Given no explicit locale is supplied and there is no current request
When `forDynamicPage(SomePage::class)` resolves the page
Then `getPageObject(SomePage::class, null)` receives `null` so the page manager can use its configured default

Given the page manager returns no page, a non-`BasePage` object, or a page with a null or empty title
When `forDynamicPage(SomePage::class)` is called
Then no breadcrumb item is added
And the method returns the same `FrontBreadcrumbs` instance without throwing or logging

Given `forHome()` has already added the home breadcrumb
When it is chained with `forDynamicPage(SomePage::class)`
Then the home item remains unchanged and the dynamic title is appended as an unlinked current item

## Edge Cases
- An explicit locale takes precedence over the current request locale.
- A missing request with no explicit locale is passed through as `null`; locale fallback remains the page manager's responsibility.
- A null, empty, or blank page title is silently skipped; the method's docblock must document this no-op behavior.
- Existing `forHome()` and `addItem()` behavior, including the `breadcrumbs` translation domain, remains unchanged.
- The dynamic title is content, not a translation key, and must not be translated.

## Out of Scope
- Introducing a `FrontBreadcrumbsInterface` contract.
- Adding a linked dynamic-page variant or route argument.
- Changing `PageManagerInterface`, page locale fallback, or page entities.
- Adding translation keys or changing host applications' existing breadcrumb behavior beyond enabling the new usage.
- Making `FrontBreadcrumbs` `final` or converting it to a `readonly` class as part of this feature.

## Technical Notes
- Public API: `public function forDynamicPage(string $pageClass, ?string $locale = null): static`, documented with `@param class-string<\Lexio\AdminBundle\Page\BasePage> $pageClass`.
- Resolve an omitted locale from `RequestStack::getCurrentRequest()?->getLocale()` and pass the result to `PageManagerInterface::getPageObject()`.
- Because the manager contract returns `?object`, accept the title only after a `BasePage`/`getTitle()` guard. Add it as `addItem($title, '', [], false)` so it is plain, unlinked, and untranslated.
- Add unit coverage in `tests/Unit/AdminCore/Breadcrumbs/FrontBreadcrumbsTest.php` using mocks for the manager, request stack/request, router, and translator, plus a real `Breadcrumbs` model where practical. Verify the service remains resolvable as `front_breadcrumbs` through bundle autowiring.
- Making the existing non-final `FrontBreadcrumbs` class `final` or `readonly` is a BC risk because host applications may subclass it; a readonly class also constrains inheritance. The least-risk choice is to preserve the existing class declaration and property-level readonly style. Adding required constructor dependencies can still affect consumers that instantiate the class manually, so the autowired service path must remain valid.
- On commit, bump the bundle revision and mention the public API and host usage `forHome()->forDynamicPage(SomePage::class)` in the commit message.
