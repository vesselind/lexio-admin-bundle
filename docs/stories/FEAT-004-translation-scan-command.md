# Feature: Translation Scan Command

## Context
**Issue Tracking:** None

Host application developers need a repeatable way to find user-facing Twig strings that bypass translation and YAML values left as '__...' placeholders. The bundle should provide the existing useful scan workflow from the aya host project while remaining aligned with the bundle's managed flat-YAML translation format.

## User Stories
- As a host application developer, I want to scan Twig templates and managed translations so that I can identify work that still needs translation.
- As a CI maintainer, I want a machine-readable result and opt-in failure status so that translation checks can be enforced in automation.

## Acceptance Criteria (Gherkin)
Given translation management is enabled and the host templates and translation directories are available
When an operator runs 'translations:scan' without a path
Then the command scans host Twig templates and all managed flat '<domain>.<locale>.yaml' translation files
And it reports hardcoded Twig text, translatable attributes, and values beginning with '__'

Given a managed translation value begins with '__'
When the command scans the file
Then it reports a 'placeholder' finding with its file, line, key, and value
And it does so for every locale, including the default locale

Given a Twig fragment is already translated or explicitly ignored
When the command scans it
Then comments, Twig expressions, Twig trans blocks, scripts, styles, and ignore-marker regions are not reported

Given findings are present
When the operator omits '--fail-on-findings'
Then the command succeeds after rendering the findings
And when the operator provides '--fail-on-findings', it returns a failure status

Given the operator requests '--format=json'
When the scan completes
Then the command emits a deterministic JSON list using 'text', 'attribute', and 'placeholder' finding types

Given a managed translation file is invalid or non-flat
When the scanner reaches it
Then the command returns a failure with the file and a safe reason

## Edge Cases
- A quoted YAML value starting with '__' is a placeholder just like an unquoted value.
- '--min-length' applies only to Twig candidates; it does not hide placeholder values such as '__'.
- Only managed root-level '<domain>.<locale>.yaml' files are scanned; '.yml', arbitrary YAML, and nested resources are ignored.
- An explicit relative path resolves from the host project root; a missing or unsupported explicit file is invalid input.

## Out of Scope
- Automatically translating, modifying, or deleting template text and translation values.
- Scanning PHP, JavaScript, CSS, arbitrary YAML files, or bundle templates.
- Adding an admin UI action, scheduling, or enabling CI failure by default.

## Technical Notes
- The command is read-only and is controlled by the existing 'translation_management.enabled' flag and 'translation_directory' setting.
- The CLI preserves the Aya options: 'path', '--format', '--fail-on-findings', '--min-length', and repeatable '--exclude'.
- The scanner must use the existing flat-YAML codec so it follows the same file and document constraints as the bundle's translation management and synchronization features.
