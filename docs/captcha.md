# CAPTCHA form fields

`CaptchaType` and `TurnstileType` are reusable, unmapped hidden fields. Their Stimulus controllers are distributed
with `@lexio/admin-bundle`. The host application owns provider selection, credentials and server-side constraints.

## Classic Google reCAPTCHA v3

The existing `CaptchaType` now uses classic v3 (`api.js`, `grecaptcha.ready()` and `grecaptcha.execute()`).
Enterprise scripts and assessment credentials are no longer used by this controller.

```php
use Lexio\AdminBundle\Form\CustomFields\CaptchaType;

$builder->add('captcha', CaptchaType::class, [
    'site_key' => $siteKey,
    'captcha_action' => 'contact',
    'captcha_enabled' => true,
    'constraints' => [$hostCaptchaConstraint],
]);
```

| Option | Default | Purpose |
| --- | --- | --- |
| `site_key` | Configured fallback, or empty | Classic v3 public site key |
| `captcha_action` | `submit` | Action passed to Google; match it during verification |
| `captcha_enabled` | `true` | Set to `false` to omit controller attributes and Google script loading |
| `captcha_error_message` | `captcha.token_error` | Message translated in the `form` domain for browser failures |
| `constraints` | `[]` | Host token validation; the field does not verify tokens itself |

The browser script is loaded once per document. Each submit obtains a fresh token, preserves the original submit
button through `requestSubmit()`, and ignores concurrent clicks. Failed execution displays an accessible error and
allows retry. Disconnecting or restoring a page from the browser back cache clears stale tokens and submission state.
The field preserves additional controllers supplied through `attr`.

When validation is disabled in the host, disable the field's controller as well. When validation is enabled, missing
credentials must reject submission. Verify tokens with a classic secret at
`https://www.google.com/recaptcha/api/siteverify`; require successful verification, the expected action and an
acceptable score. Handle invalid responses and network failures as validation failures. See Google's
[v3 guide](https://developers.google.com/recaptcha/docs/v3) and
[server verification reference](https://developers.google.com/recaptcha/docs/verify).

## Cloudflare Turnstile

`TurnstileType` keeps the `turnstile` identifier and its independent `site_key` option. Supply a host validation
constraint and verify the token using the Turnstile secret. A Symfony form type extension can supply default
constraints and CMS credentials for either type without creating duplicate form types or JavaScript controllers.

## Asset registration

Reprise hosts enable the identifiers they use in `assets/controllers.json`:

```json
{
    "controllers": {
        "@lexio/admin-bundle": {
            "captcha": {"enabled": true, "fetch": "lazy"},
            "turnstile": {"enabled": true, "fetch": "lazy"}
        }
    }
}
```

Admin hosts using `registerLexioAdminControllers()` already receive both controllers. Build changes with
`make assets-build`, then refresh the installed package and build the host's browser assets.
