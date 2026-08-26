# LexioAdminBundle Documentation

> Work in progress — this is a structural draft to be filled with content.

---

## 1. Getting Started

- **1.1** Installation & Requirements (PHP >= 8.4, Symfony 7.2+/8.x, npm dependencies)
- **1.2** Bundle Configuration (`lexio_admin` semantic config tree)
  - `default_locale`, `locales`, `admin_route_prefix`
  - `listing_items_per_page`
  - `deepl_translation_api_key`, `google_translation_api_key`
  - `user_entity_class`
  - `front_home_page_route`
- **1.3** Overriding Templates & Styles

---

## 2. Architecture Overview

- **2.1** Namespace & Directory Map
- **2.2** Service Wiring (`config/services.yaml`)
- **2.3** Dependency Injection & Compiler Passes (`ResolveNotificationUserPass`)
- **2.4** Twig Namespaces & UX Components Default Directory Mapping
- **2.5** Key Third-Party Integrations (Gedmo, KNP Paginator, Intervention, PhpSpreadsheet, DeepL, Symfony UX)

---

## 3. AdminCore CRUD Framework

- **3.1** `BaseCrudController` — Lifeycle & Service Locator
  - `renderListing()`, `renderCreate()`, `renderUpdate()`, `renderDelete()`, `renderDetails()`, `renderSeo()`
  - Flash messages, Breadcrumbs, Auto-translation
- **3.2** Listing Pages
  - `ListingContext` — fluent builder for listing configuration
  - `Column` — column definition & sorting
  - Pagination (KNP)
- **3.3** Fields (22+ Field Types)
  - `BaseField` — abstract base & field template resolution
  - `IdField`, `TitleField`, `EnumField`, `BooleanField`, `DateTimeField`, `NumberField`, `ColorField`
  - `CollectionField`, `CollectionCountField`, `DropdownActionsField`, `FileDownloadField`
  - `IconField`, `HasRegisteredField`, `LogField`, `RepealedField`
  - `InlineEditField` (live component), `ToggleSwitcherField` (live component)
  - `StripeInvoiceUrlField`, `ActTitleField`, `AiQuestion`, `AiTaskTitleField`, `EnumSelectOptionField`
  - Custom field templates (`templates/admin/fields/`)
- **3.4** Form Handling
  - `FormContext` — create/update form orchestration
  - Tabs (`Tab`, `TabManager`, `SeoTab`, `ActMetaTab`)
  - Actions (`Action`, `DeleteAction`, `UpdateAction`, `DetailsAction`)
  - Admin lifecycle events (`BeforeEntityPersisted`, `AfterEntityPersisted`, `BeforeEntityUpdated`, `AfterEntityUpdated`)
- **3.5** Bulk Actions (`BulkAction`, `BulkContext`)
- **3.6** URL Generation (`AdminUrlGenerator`)
- **3.7** Route Loading (`RouteLoader`)
- **3.8** QueryString Value Resolver (`MapQueryStringValueResolver`)
- **3.9** Filter System
  - `BaseFilterInterface`, `BaseFilter` DTO
  - `BaseFilterType` — auto-generated filter forms
  - `EntityFilterer` — QueryBuilder builder with filter, search, sorting

---

## 4. Entities

- **4.1** File & Image contracts (`FileEntityInterface`, `ImageEntityInterface`)
  - Concrete entities and repositories are owned by the host application.
- **4.2** Page & Content Items (`Page`, `ContentItem`, `PageManager`)
  - Translation (Gedmo Translatable)
  - SEO data trait (`HasSeoData`)
- **4.3** Menus (`HeaderMenu`, `FooterMenu`, `SortableMenu`)
- **4.4** Mail Templates — host-application entities and repositories
- **4.5** System Notification contracts (`NotificationUserInterface`, `NotificationBellProviderInterface`)
  - Concrete notifications, repositories, messages, and handlers are owned by the host application.
- **4.6** Scheduled jobs — scheduling and execution history are owned by the host application.
- **4.7** Security Logs (`SecurityLog`)
  - Mapped to the legacy `activity_log` table to preserve existing audit history.
- **4.8** Contacts — host-application entity, repository, filter, and form
- **4.9** Email Delivery — host-application entity and repository
- **4.10** Password Reset — host-application entity and repository
- **4.11** Seeders (`Seed`)
- **4.12** Translatable & Sortable Traits

---

## 5. Admin Menu System

- **5.1** `MenuBuilder` — fluent sidebar menu builder
- **5.2** `MenuBadge` — optional static or callback-driven badge metadata
- **5.3** `Link`, `SubMenu`, `Separator`
- **5.4** `MenuItemInterface`
- **5.5** Rendering via `Admin:SideMenu` live component

---

## 6. Dashboard

- **6.1** `DashboardBuilder` — fluent builder for stat cards
- **6.2** `DashboardItem`
- **6.3** Dashboard template

---

## 7. Breadcrumbs

- **7.1** `AdminBreadcrumbs` — admin trail (uses `huluti/breadcrumbs-bundle`)
- **7.2** `FrontBreadcrumbs` — front-end trail

---

## 8. Live Components (Symfony UX)

- **8.1** `Admin:InlineEdit` — inline text editing with LiveAction save
- **8.2** `Admin:ToggleSwitcher` — boolean toggle with live update
- **8.3** `Admin:BulkActions` — bulk action controls
- **8.4** `Admin:SideMenu` — admin sidebar
- **8.5** `Admin:NotificationBell` — notification bell dropdown
- **8.6** `Admin:ExportExcel` — Excel export button
- **8.7** `Admin:ItemsPerPage` — per-page selector
- **8.8** `Admin:BaseModal` / `Admin:OpenBaseModalBtn` / `Admin:BaseModalBody` — modal system
- **8.9** `Admin:PublishBlog` — blog publish toggle
- **8.10** `ConfirmationModal` — generic confirmation dialog
- **8.11** `ImageSelector` — image picker
- **8.12** `Img` — responsive image component
- **8.13** `FieldComponentTrait` — shared trait for field-based live components

---

## 9. Custom Form Types

- **9.1** `CKEditorType` — CKEditor 5 integration
- **9.2** `InputImageSelectorType` — image selection field
- **9.3** `AssociationModalType` — entity association picker via modal
- **9.4** `VanillaDatepickerType` — date picker
- **9.5** `CaptchaType` — Turnstile CAPTCHA
- **9.6** `BaseFilterType` — auto-generated filter forms
- **9.7** Standard admin forms (`SeoType`, `BlogShortType`, `PageObjectType`, `HeaderMenuType`, `FooterMenuType`)

---

## 10. Twig Extensions & Runtime

- **10.1** `AdminExtension`
  - `render_field()` — render a listing field
  - `render_admin_menu()` — render the sidebar menu
  - Global: `frontHomePageRoute`
- **10.2** `AppExtension`
  - `stimulus_modal()`, `get_locales()`, `absolute_path()`, `absolute_asset()`
  - Filters: `formatted_date`, `formatted_datetime`, `strip_words`, `highlight`, `list_style`, `add_links`, `cast_to_array`, `camel_to_title`, `anonymize`, `format_bytes`
- **10.3** `AdminExtensionRuntime`
- **10.4** `ImageRuntimeExtension`

---

## 11. Email System

Mailables, templates, persistence, and the synchronization command are host-application
responsibilities. The bundle retains notification contracts used by host implementations.

---

## 12. System Notifications

The concrete notification entity, repository, filter, enum, message, handler, provider,
and admin templates are owned by the host application. The bundle provides the reusable
contracts and notification-bell component:

- **12.1** `NotificationBellProviderInterface`
- **12.2** `NotificationUserInterface`
- **12.3** Doctrine target entity resolution via `ResolveNotificationUserPass`

---

## 13. File & Image Management

- **13.1** `FileManager` — upload, storage, URL generation
- **13.2** `FileValidator` — file validation
- **13.3** `ImageCacheConfig` / `ImageCacheResolver` — Intervention-based caching
- **13.4** `InterventionImageManager` — image processing
- **13.5** `FileEventListener` — physical file cleanup on entity removal
- **13.6** `FileAccessType` enum
- **13.7** `FileEntityInterface` / `ImageEntityInterface`

---

## 14. Auto-Translation

- **14.1** `EntityAutoTranslator` — translates Gedmo-translatable fields into all locales
- **14.2** `DeeplAutoTranslator` — DeepL API integration
- **14.3** `AutoTranslatorInterface` / `EntityAutoTranslatorInterface`
- **14.4** `AdminLifecycleSubscriber` — triggers auto-translation after entity persist/update
- **14.5** `Settings::DEEPL_TRANSLATION_KEY`, `Settings::AUTO_TRANSLATE_ON_CREATE`

---

## 15. Events & Lifecycle

- **15.1** Admin Lifecycle Events (`BeforeEntityPersisted`, `AfterEntityPersisted`, `BeforeEntityUpdated`, `AfterEntityUpdated`)
- **15.2** Security Events (`SecurityLogEvent`, `EmailConfirmed`, `ForgotPassword`, `PasswordChanged`, `UserHasRegistered`)
- **15.3** Event Subscribers
  - `AdminLifecycleSubscriber` — auto-translation trigger
  - `ModalContextSubscriber` — Turbo Stream responses for modals
  - `SecurityLogSubscriber` — security logging
  - `SitemapSubscriber` — sitemap generation
- **15.4** Event Listeners
  - `FileEventListener` — cleanup physical files
  - `PageAttributeListener` — `#[Page]` attribute to Twig global

---

## 16. Console Commands

- **16.1** `make:admin-crud` — full code generator (controller, filter, repo, translations, tests, factory)
- **16.2** `lexio:seeder:list-tasks` — list available seed tasks
- **16.3** `lexio:seeder:load` — run seeders
- **16.4** `lexio:seeder:rerun-task` — re-run a specific seed task

---

## 17. Seeders

- **17.1** `SeedLoader` — seeder orchestrator
- **17.2** `Seed` entity tracking
- **17.3** `SeedersRegistryInterface`
- **17.4** Seeder files (`src/Seeder/files/`)

---

## 18. Scheduling

The bundle does not provide a scheduler or cron worker. Host applications can use
Zenstruck Schedule Bundle (or another scheduler) and persist execution history in
their own domain model.

---

## 19. Frontend Assets

- **19.1** Stimulus Controllers (28 controllers)
  - Modal handling (`base-modal`, `open-base-modal`, `modal`)
  - Form behavior (`autosubmit`, `autocomplete-search`, `bulk-action`, `check-selection`)
  - CKEditor 5 integration (`ckeditor`)
  - Image handling (`image-selector`, `input-image-selector`, `image-gallery`)
  - File upload (`file-upload-form`)
  - Date picker (`vanilla-datepicker`, `vanilla-datepicker-live`)
  - Sorting (`sortable`)
  - Utility (`clipboard`, `toggle-class`, `tooltip`, `collapsable-sidebar`, `navigate-turbo`, `onscroll`)
  - Specialized (`rating`, `textarea-placeholders`, `links-search-field`, `add-autocomplete-item`, `association-modal-type`, `captcha`, `flash-message`)
- **19.2** CKEditor Plugins (`CustomUploadAdapter`, `InsertImage`, `InsertImageViaURL`)
- **19.3** Flash messages (`flash.js` + Turbo Stream)
- **19.4** SCSS Architecture
  - `admin.scss` entry point
  - Components (sidebar, tables, tabs, cards, breadcrumbs, notifications, etc.)
  - Layout (`header-navbar.scss`)
  - Mixins, utilities, variables
  - Datepicker & flash-message styles
  - Font Awesome integration

---

## 20. Search

- **20.1** `SearchableEntityInterface` — contract for searchable entities
- **20.2** `EntitySearcher` — unified search across entities
- **20.3** `SearchableParam` model
- **20.4** `EntityFilterer` — full-text search in listings

---

## 21. Excel Import/Export

- **21.1** `ExcelExporter` — generate .xlsx from entities
- **21.2** `ExcelImporter` — import entities from Excel
- **21.3** `Admin:ExportExcel` live component

---

## 22. Enums

- **22.1** `Flash` — `SUCCESS`, `ERROR`, `WARNING`, `INFO`
- **22.2** `Settings` — bundle setting names
- **22.3** `FileAccessType` — `PUBLIC`, `PRIVATE`
- **22.4** `FileTypes`
- **22.5** `SecurityEvents`
- **22.6** `Colors`
- **22.7** `Roles`
- **22.8** `TranslatableEnum` trait

---

## 23. Contract Interfaces

- **23.1** `FileEntityInterface` / `ImageEntityInterface`
- **23.2** `BaseFilterInterface`
- **23.3** `AutoTranslatorInterface` / `EntityAutoTranslatorInterface`
- **23.4** `NotificationBellProviderInterface`
- **23.5** `NotificationUserInterface`
- **23.6** `PageManagerInterface`
- **23.7** `SearchableEntityInterface`
- **23.8** `SeedersRegistryInterface`

---

## 24. Attributes

- **24.1** `#[FieldType]` — map entity properties to field types
- **24.2** `#[Page]` — inject page object as Twig global on controllers

---

## 25. Turbo Integration

- **25.1** `TurboRedirectResponse` — Turbo-Frame aware redirects
- **25.2** `FormContext` modal detection via `Turbo-Frame` header
- **25.3** `ModalContextSubscriber` — Turbo Stream responses for association modals
- **25.4** Flash messages via Turbo Stream (`flash.stream.html.twig`)

---

## 26. Settings & Configuration Model

- **26.1** `Setting` model
- **26.2** `SettingGroup` model
- **26.3** `Settings` enum

---

## 27. Utility Services

- **27.1** `AdminUtils`
- **27.2** `Utils` — snake_case, randomString, classNameToSnake
- **27.3** `TextPurifier`
- **27.4** `ImageLoaderTrait`

---

## 28. Maker Code Generators

- **28.1** `make:admin-crud` — scaffold controller, filter, repo method, translations, tests, factory
- **28.2** Maker templates (`templates/maker/`)

---

## 29. Testing

- **29.1** Test setup (`phpunit.xml.dist`)
- **29.2** Testing admin controllers
- **29.3** Testing live components
- **29.4** Maker-generated test template

---

## 30. Modals

- **30.1** `ModalContextAwareTrait`
- **30.2** `ConfirmationModal` component
- **30.3** Base modal live components (`Admin:BaseModal`, `Admin:OpenBaseModalBtn`, `Admin:BaseModalBody`)
- **30.4** Association modal type
- **30.5** Modal templates (`templates/admin/_modals/`)
