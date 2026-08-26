# Feature: Synchronize Translation Packages Between Environments

## Context
**Issue Tracking:** None

The starter application must act as both a sender and receiver of translation packages. A translation package is the complete supported collection from the host application's `translations/` directory. This enables a developer to upload local translations to a deployed instance and download deployed translations back to the local instance without manually copying files.

## User Stories
- As a developer, I want to upload my local translation package to the deployed application so that new and updated translations are synchronized remotely.
- As a developer, I want to download the deployed translation package into my local application so that I can receive remote translation changes.
- As an administrator in a non-production environment, I want to send or receive translations from the translation administration page so that I can synchronize them without using the console.
- As an operator, I want synchronization requests authenticated independently of admin sessions so that the API can be used safely by console commands.
- As an operator, I want optional HTTP Basic Auth credentials so that synchronization works when the deployed application is protected by `.htaccess`.

## Acceptance Criteria (Gherkin)
Given synchronization is configured in both environments
When the upload command is executed locally
Then the complete local translation package is sent to the configured deployed API endpoint
And the command reports success or returns a non-zero exit code with a safe error

Given the deployed application receives a valid authenticated translation package
When it imports the package
Then missing supported translation files are created
And new translation keys are inserted
And existing matching keys are updated with incoming values
And local files and keys absent from the incoming package are preserved

Given synchronization is configured locally
When the download command is executed
Then the deployed translation package is downloaded from the configured endpoint
And it is merged into the local package using the same non-destructive rules

Given an authenticated administrator opens the translation administration page in a non-production environment
When the page is rendered
Then right-aligned Send and Receive buttons are displayed in the page action area
And Send uploads the local translation package to the configured deployed application
And Receive downloads and merges the deployed translation package into the local application

Given the application runs in the production environment
When an administrator opens the translation administration page
Then the Send and Receive buttons are not rendered
And direct requests to the corresponding UI actions are rejected without performing synchronization

Given an administrator submits either synchronization action from the translation administration page
When the operation succeeds or fails
Then the request is protected against CSRF
And the administrator receives a translated success or failure message

Given an API request contains a valid timestamped signature derived from `APP_SECRET` and the configured synchronization salt
When the request reaches the translation package API
Then the request is authorized without requiring an admin session

Given an API request has a missing, invalid, or expired signature
When the request reaches the translation package API
Then it is rejected without exposing secret material or modifying translations

Given HTTP Basic Auth credentials are configured for the deployed endpoint
When either synchronization command performs an HTTP request
Then the credentials are sent using HTTP Basic Authentication
And when credentials are omitted no Basic Authentication header is sent

Given a package contains an invalid filename, path traversal, duplicate key, nested YAML, unsupported value, malformed archive, or invalid YAML
When the receiver validates it
Then the package is rejected before translation files are modified

Given the API receives a valid authenticated download request
When the package is exported
Then all supported translation YAML files are returned as one downloadable package

## Edge Cases
- Empty translation directory: export a valid empty package and perform a no-op merge.
- New files and keys: create them while preserving the established flat YAML conventions.
- Missing remote files or keys: never delete the receiver's corresponding local content.
- Conflicting values: the incoming value wins for the same file and key.
- Unsupported translation resources: ignore or reject them according to package validation; never write them into the translation directory.
- Network timeout, DNS failure, HTTP Basic Auth failure, or non-success API response: leave local translations unchanged and return a failed command status.
- Admin synchronization actions: preserve the selected translation file and current listing filters after redirecting back to the translation page.
- Replay attempts: reject signed requests outside the accepted timestamp window.
- Package safety: reject absolute paths, parent traversal, nested directories, excessive files, or excessive uncompressed package size.

## Out of Scope
- Deleting translation files or keys through synchronization.
- Synchronizing XLIFF, PHP, JSON, nested YAML, or other resource formats.
- Automatic scheduled synchronization, conflict prompts, revision history, or rollback UI.
- Synchronizing application files outside the configured translations directory.
- Replacing the existing admin translation editor.

## Technical Notes
- The package format is ZIP and contains supported flat `*.yaml` translation files only.
- The API exposes upload and download operations and is implemented by the reusable bundle, while the host owns the concrete routed controller.
- Request authentication uses HMAC-SHA256 with `APP_SECRET`, a bundle-configured salt, and a timestamped request representation.
- The deployed base URL comes from `DEPLOYED_APP_URL`; the relative API path and synchronization salt are bundle configuration.
- Optional HTTP Basic Auth username and password must be configurable without committing production credentials.
- Both upload and download commands are provided by the bundle and operate against the configured deployed endpoint.
- The reusable bundle translation controller exposes the non-production Send and Receive actions; the host controller continues to own the concrete route prefix and authorization policy.
