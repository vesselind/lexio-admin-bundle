# Architecture: Translation Scan Command

## Existing Patterns Observed
- Bundle commands are explicitly registered through 'config/services.yaml', use SymfonyStyle, and delegate work to services.
- Translation management already provides a configured host translation directory, an enablement flag, and FlatTranslationDocumentCodec for supported flat YAML documents.
- The bundle's command tests use CommandTester; translation services use isolated temporary directories in unit tests.

## Technical Design

### Components
- TranslationScanner: internal, final readonly service that resolves scan targets, walks Twig files with native iterators, validates managed YAML through FlatTranslationDocumentCodec, and returns deterministic finding arrays.
- ScanTranslationsCommand: thin console boundary registered as 'translations:scan'; it validates CLI options, renders text or JSON, and maps invalid input to INVALID and scan errors to FAILURE.
- TranslationScanException and TranslationScanInputException: internal exception hierarchy separating operational scan failures from invalid CLI targets.

### Implementation Order
1. Add the internal scanner and exception hierarchy using the existing translation enablement and directory parameters.
2. Register the scanner and command explicitly in the bundle service configuration.
3. Add command, service, and wiring tests before validating the full quality pipeline.
4. Document the command and placeholder convention in the translation guide.

### Interfaces / Contracts
- No new public PHP Contract is required: this is an internal console capability, and host applications do not need to type-hint or decorate its scanner.
- JSON findings retain Aya-compatible 'file', 'line', 'type', and 'value' fields. Attribute findings add 'attribute'; placeholder findings add 'key'.

## Applicable Rules
The developer MUST read these rules before implementing:
- symfony-bundle.md — explicit bundle service registration, configuration reuse, and internal/public API boundaries.
- coding-standards.md — strict types, final/readonly services, and constructor injection.
- console-commands.md — thin command, SymfonyStyle, exit-code contract, and CommandTester.
- testing.md — isolated unit tests and success/error coverage.
- i18n.md — translation-aware scan semantics and text-only YAML values.
- security.md — safe local path handling and no secret output.
- error-handling.md — meaningful scan-specific exceptions and no swallowed errors.

## Risks and Trade-offs
- The scanner uses native recursive iterators rather than a new direct Finder dependency, keeping the bundle dependency surface unchanged.
- Exact template parsing remains heuristic by design; the copied ignore rules reduce false positives without attempting to compile Twig.
- Restricting placeholder scans to managed YAML files avoids reporting resources the bundle cannot edit or synchronize.
