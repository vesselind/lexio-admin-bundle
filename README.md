# Lexio Admin Bundle

A Symfony 7+ bundle that provides a rich admin CRUD layer with:

- **Listing** — sortable / filterable paginated tables (`ListingContext`, `Column`, `BaseField` subtypes)
- **Forms** — multi-tab form context with locale switching, modal support, and Turbo integration (`FormContext`, `TabInterface`)
- **Actions** — per-row dropdown actions with optional confirmation modals
- **Bulk actions** — multi-row operations
- **Menu builder** — nested menus with active-link detection
- **Dashboard builder** — stat cards
- **Breadcrumbs** — admin & front breadcrumbs (requires `huluti/breadcrumbs-bundle`)
- **Settings** — generic key-value `Setting` entity + `SettingRepository`
- **File & Image entities** — base entities with repository helpers
- **Code generator** — `make:admin` command scaffolds controller, filter DTO, repository method, translations, factory and tests
- **MapQueryString resolver** — extends Symfony's built-in resolver to auto-hydrate entity-typed filter properties

## Requirements

| Dependency | Version |
|---|---|
| PHP | ≥ 8.4 |
| Symfony | ^7.2 \|\| ^8.0 |
| Doctrine ORM | ^3.0 |
| Doctrine Bundle | ^3.0 |
| KnpPaginatorBundle | ^6.0 |

## Installation

### From Packagist (once published)

```bash
composer require lexio/admin-bundle
```

### Local installation (development / before publishing)

When the bundle lives only on your local machine, use a Composer `path` repository so your app can require it directly from disk — no Git remote or Packagist needed.

**1. Tell your app where the bundle lives**

In your **app's** `composer.json`, add a `repositories` entry pointing at the absolute (or relative) path of the bundle directory:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../lexio-admin-bundle",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

> `"symlink": true` means Composer will create a symlink inside `vendor/` instead of copying files, so every change you make in the bundle directory is reflected in the app immediately without re-running `composer install`.

**2. Require the package**

```bash
composer require lexio/admin-bundle:*@dev
```

The `*@dev` constraint tells Composer to accept any version (including `dev-main` / no tag) from the local path.

**3. Register the bundle**

In your app's `config/bundles.php`:

```php
return [
    // …
    Lexio\AdminBundle\LexioAdminBundle::class => ['all' => true],
];
```

**4. Verify the symlink**

```bash
ls -la vendor/lexio/admin-bundle
# → should point to ../lexio-admin-bundle (or the absolute path you configured)
```

> **Tip — Windows users:** Composer symlinks require either Developer Mode enabled or running the terminal as Administrator. If symlinks fail, remove `"symlink": true` and Composer will copy the files instead (you'll need to re-run `composer install` after each bundle change).

## Frontend Assets

The bundle does not distribute frontend assets. Host applications own their Stimulus controllers,
JavaScript, CKEditor plugins, and styles under their own `assets/` directory and declare the
required npm dependencies in the application-level `package.json`.

## Configuration

```yaml
# config/packages/lexio_admin.yaml
lexio_admin:
    default_locale: en
    locales: [en, bg]
    admin_route_prefix: /admin
    listing_items_per_page: 20
    user_entity_class: App\Entity\User   # required only for HasRegisteredField
```

## Usage Highlights

### Listing

```php
$listing
    ->addColumn('id',    new IdField())
    ->addColumn('title', new TitleField(linkToRoute: 'admin.blog.update', routeParams: ['id' => 'id']))
    ->addColumn('status', new EnumField())
    ->addBulkAction(new BulkAction('admin.blog.bulk_delete', 'Delete selected'))
    ->setEntityFqcn(Blog::class)
    ->refreshColumnsSortability();
```

### Form Context

```php
$context
    ->setEntityInstance($blog)
    ->setPageTitle('admin.blog.update')
    ->addTab(new Tab('General', 'admin.blog.update', ['id' => $blog->getId()]))
    ->addTab(new SeoTab())
    ->addAction(UpdateAction::new('admin.blog.update'))
    ->addAction(DeleteAction::new('admin.blog.delete'));
```

### Settings

```php
// In your app, extend CreateSettingsCommand:
class CreateSettingsCommand extends \Lexio\AdminBundle\Command\CreateSettingsCommand
{
    public function register(): void
    {
        $this->repository->create('SITE_NAME', 'My Website');
        $this->repository->create('GOOGLE_ANALYTICS_ID', '');
    }
}
```

### Code Generator

```bash
php bin/console make:admin
```

Prompts for an entity name and scaffolds:
- `src/Filter/{Entity}Filter.php`
- `src/Controller/Admin/{Entity}Controller.php`
- `filtered()` method injected into the existing repository
- Translation keys in `translations/form.*.yaml` and `translations/admin.*.yaml`
- Foundry factory (if available)
- Integration test skeleton

## Missing Files Warning

The following items were extracted from an existing application and **may require app-specific files that were not included**:

| File | Missing dependency |
|---|---|
| `AdminBreadcrumbs` / `FrontBreadcrumbs` | `huluti/breadcrumbs-bundle` |
| `MakeAdminSectionCommand` | `symfony/maker-bundle`, `App\Entity\*` classes at scaffold time |
| Twig templates under `templates/` | May reference app-specific partials |

Please review and adapt templates for your application.

## Namespace Map (migration from `App\AdminCore\*`)

| Old | New |
|---|---|
| `App\AdminCore\*` | `Lexio\AdminBundle\AdminCore\*` |
| `App\Entity\Setting` | `Lexio\AdminBundle\Entity\Setting` |
| `App\Utils\Utils` | `Lexio\AdminBundle\Utils\AdminUtils` |
| `App\Response\TurboRedirectResponse` | `Lexio\AdminBundle\Http\TurboRedirectResponse` |
| `App\Filter\BaseFilter` | `Lexio\AdminBundle\Contract\Filter\BaseFilter` |
| `App\Form\Filter\BaseFilterType` | `Lexio\AdminBundle\Form\Filter\BaseFilterType` |
| `App\Entity\FileEntityInterface` | `Lexio\AdminBundle\Contract\File\FileEntityInterface` |
| `App\Entity\ImageEntityInterface` | `Lexio\AdminBundle\Contract\File\ImageEntityInterface` |

Concrete file and image entities and their repositories belong to the host application. Implement
the bundle contracts and alias `FileRepositoryInterface` to the host file repository service.

Contact submissions, including the entity, repository, filter, form, and admin details template,
are also owned by the host application.

System notifications, including the entity, repository, filter, enum, message, handler, provider,
and admin templates, are owned by the host application. The bundle retains only reusable
notification contracts and the notification-bell component.

The mail domain (mailables, sender, templates, `MailTemplate`/`EmailDelivery` entities and
repositories, forms, and template synchronization command) is also owned by the host application.
The host application owns its notification mailer contract and adapter.
Registration, email confirmation, and password-reset flows (including the `ForgotPassword`
entity and repository) are host-application responsibilities.

## License

MIT

