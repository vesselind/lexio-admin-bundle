# User administration

The abstract `Lexio\AdminBundle\Controller\Admin\UserController` provides user listing,
creation, update, delete, bulk delete, roles and profile actions. The host application
extends it and adds its route prefix and authorization attribute, as with `FileController`
and `ModalController`. Its inherited routes keep the names `admin.user.*`.

The host user entity implements `Contract\User\UserEntityInterface`. It remains a
Doctrine entity owned by the application. The controller looks up users through
`getEntityFqcn()` using the numeric route ID and returns 404 for missing users.

The subclass supplies these methods:

| Method | Host integration |
| --- | --- |
| `getEntityFqcn()` | User entity class |
| `userEntity()` | New user entity |
| `getUserFormType()` | User form, including the `show_blocked` option for profiles |
| `getPasswordFormType()` | Form with unmapped `oldPassword` and `newPassword` fields |
| `getUserListingFilter()` | User listing filter extending `BaseFilter` |
| `getRoleChoices()` | Backed enum cases allowed by the host application |

Roles are filtered against the supplied enum values, deduplicated and always include
`ROLE_USER`. Profile password changes require a valid current password. Forms and
role definitions stay in the host application.

Role submissions require a CSRF token scoped to `admin.user.roles.<user ID>` in
the `_token` field. The bundle roles template supplies it; custom forms must also
include it. Enable Symfony's `framework.csrf_protection` in the host application.
Profile and password forms must have distinct names. Requests submitting both
forms are rejected with HTTP 400 before either form is bound.

The controller renders bundle templates by default:

| Override method | Default template |
| --- | --- |
| `getListingTemplate()` | `@LexioAdmin/admin/user/listing.html.twig` |
| `getUpdateTemplate()` | `@LexioAdmin/admin/user/update.html.twig` |
| `getRolesTemplate()` | `@LexioAdmin/admin/user/roles.html.twig` |
| `getProfileTemplate()` | `@LexioAdmin/admin/user/profile.html.twig` |

Applications only override a template method when they need different rendering.
Their custom template extends the corresponding bundle template and overrides
the relevant block. For example, adding a profile field:

```php
protected function getProfileTemplate(): string
{
    return 'admin/user/profile.html.twig';
}
```

```twig
{% extends '@LexioAdmin/admin/user/profile.html.twig' %}

{% block profile_form_fields %}
    {{ parent() }}
    {{ form_row(profileForm.phone) }}
{% endblock %}
```

The listing exposes `user_listing_toolbar`, `user_listing_item`, `user_listing_avatar`,
`user_listing_details` and `user_listing_actions`. The profile exposes `profile_form_fields` and
`password_form_fields`. Roles expose `main_form`, `main_form_content` and
`role_choices`, preserving the existing Turbo frame IDs. The reusable password
change fragment is `@LexioAdmin/admin/user/password_change.html.twig`.

The default views follow the starter's user presentation: the listing reads
`avatarColor`, `initials`, `blocked`, `confirmedAt` and `createdAt` alongside the
user contract fields. The profile renders `email`, `firstName`, `lastName` and
`avatarColor` form fields. Applications with different fields override the relevant
blocks in a template extending these defaults.

The host aliases `Contract\User\UserInvitationInterface` to its own invitation service.
Its `invite()` method hashes an initial password, persists the account and password
setup record, and sends the application's invitation email. It returns the expiry time
used by the existing `user_created_from_admin` success message. The starter's
`App\Service\AdminUserInvitation` is an example implementation.

The inherited constructor injects the invitation service and
`UserPasswordHasherInterface`; Symfony autowires these dependencies on the host
subclass. No bundle controller route import or bundle user entity is required.

English and Bulgarian translations for the controller messages and user routes ship
in the bundle's `admin`, `messages` and `validators` domains. Host translation values
continue to override these defaults.
