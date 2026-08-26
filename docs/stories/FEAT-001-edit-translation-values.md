# Feature: Edit Translation Values from the Admin

## Context
**Issue Tracking:** None

The starter application uses Symfony YAML translation files stored in `translations/`, with one flat translation key per line and one file per domain and locale. Administrators need to update translation values from the admin panel without developer intervention.

## User Stories
- As an authenticated administrator, I want to see the available translation domains and locales so that I can choose the translations I need to maintain.
- As an authenticated administrator, I want to see every translation key and its value for a selected domain and locale so that I can review the current copy.
- As an authenticated administrator, I want to edit a translation value inline so that the corresponding YAML file is updated immediately.

## Acceptance Criteria (Gherkin)

Given an authenticated administrator opens the translation administration page
When the page is rendered
Then available translation files are listed by domain and locale

Given an authenticated administrator selects a translation file
When the page is rendered
Then all supported flat translation keys and their current values are listed
And each value has an inline-editable input

Given an authenticated administrator changes a value and submits the inline edit
When the translation file is writable and valid
Then only the selected translation record is updated in its original domain/locale file
And the updated value is visible after the request completes

Given an authenticated administrator submits a value containing YAML-significant characters
When the update is written
Then the resulting file remains valid YAML
And the value is stored as the submitted string

Given a requested translation file, key, or record cannot be found
When an administrator attempts to update it
Then the update is rejected with a safe error response
And no other translation file is modified

Given the target translation file cannot be written or contains unsupported nested records
When an administrator attempts to update it
Then the update is rejected with a safe error response
And the original file remains unchanged

Given an unauthenticated or unauthorized user requests the translation administration page or update endpoint
When the request is processed
Then access is denied according to the host application's existing admin security rules

## Edge Cases
- Empty submitted values: preserve an empty string as a valid translation value.
- Quotes, colons, hashes, apostrophes, and line breaks in values: serialize safely so the YAML remains parseable; multiline values must not corrupt neighboring records.
- Duplicate keys: reject the file/update rather than silently changing an ambiguous record.
- Nested YAML records, lists, aliases, or unsupported scalar types: do not expose them for editing and do not rewrite the file.
- Missing or unreadable translation directory/file: show a safe failure and make no write.
- Concurrent updates: write the selected file atomically so a partial file is never left behind.
- File traversal input: domain and locale identifiers must resolve only to configured translation files.

## Out of Scope
- Creating or deleting translation files, domains, locales, or keys.
- Editing XLIFF, PHP, JSON, or non-flat YAML translation resources.
- Translating values automatically or synchronizing values between locales.
- Bulk editing, search, import/export, or audit history.
- Changing the host application's authorization model or admin navigation policy.

## Technical Notes
- The bundle must remain host-application agnostic and receive the translation directory through bundle configuration.
- The starter app follows `snake_case_of_domain.locale.yaml` and flat dot-notation keys; the implementation must preserve that convention.
- The host controller extends the bundle controller so the app can represent the result and add app-specific behavior without duplicating update logic.
- User-facing UI text must use translation keys and the existing bundle/admin translation conventions.
