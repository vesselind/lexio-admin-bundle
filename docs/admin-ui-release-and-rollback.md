# Admin UI Migration Release and Rollback

This runbook describes how to release the coordinated changes in
`lexio/admin-bundle` and `lexio-admin`. It is a release procedure, not a substitute for the
bundle and host quality gates.

## Release classification

The current migration state cannot be published as one minor release. Two changes remove
published bundle behavior:

- `templates/base.html.twig` and the unscoped `templates/flash.stream.html.twig` are no longer
  published by the bundle;
- the public-site-only `onscroll` and `rating` controllers are no longer present in the bundle
  package.

Consumers of those paths or package controllers need a major-version migration. The additive
admin ownership work can be released as a minor version only when the release still retains any
published compatibility paths that it claims to support. The current worktree is therefore a
pre-release major-migration state; no version or Git tag is implied by these files.

## Release sequence

1. Restore a working `make` command in the development environment.
2. In the bundle, run `make assets-test`, `make assets-build`, and `make ci`. Confirm that
   `assets/dist/`, `assets/package.json`, translations, and the documented Twig contracts are
   included in the release artifact.
3. Publish and tag the bundle version. Use a major version for the current public-template and
   public-controller removals. Record the old public paths and the replacement paths in the
   release notes.
4. In the host, replace the development path dependency (`lexio/admin-bundle: *@dev`) with the
   published bundle version when preparing a release. Keep the path repository only for local
   coordinated development.
5. Refresh the host's installed bundle package, then run `make assets-install`, `make assets-build`,
   `make assets-test`, and `make ci` in the host. Do not hand-edit `node_modules` or generated
   build output to make the checks pass.
6. Verify the critical admin workflows in a browser: dashboard, listing, details, create/update,
   association modal, CKEditor, image selector, datepicker, bulk actions, mobile sidebar, focus
   navigation, reduced-motion behavior, and high-contrast readability.
7. Commit host dependency/source cleanup separately from the bundle release. This keeps the host
   cleanup independently revertible.

The host must not manually delete tracked compiled assets. Keep the previous generated output
recoverable until the new bundle package has installed and the new build has succeeded. If a build
fails after partially writing output, restore the previous generated artifact from Git or the
release artifact before retrying.

## Rollback procedure

### Before host cleanup

Keep the previously released bundle version installed and postpone host cleanup. Fix or revert
the bundle release candidate first. No host source restoration is needed because the old host
copies are still present at this point.

### After host cleanup

1. Revert the host cleanup commit, or restore only the affected feature-slice commit. Do not reset
   unrelated application work.
2. Restore the host's previous bundle version in its dependency configuration and refresh the
   installed package using the normal project dependency workflow.
3. Rebuild with `make assets-build` and run `make assets-test` and `make ci` in the host.
4. If only one workflow failed, keep already verified bundle-owned slices and restore only that
   workflow's host source or dependency configuration.
5. Record the failing browser workflow, bundle version, host version, generated asset manifest, and
   rollback commit before attempting another release.

Use Git revert or a dedicated follow-up commit for recovery. Do not use a broad destructive reset
in a worktree that contains unrelated application changes.

## Release checklist

- [ ] The bundle release classification is recorded as minor (additive/compatible) or major
  (published path/controller removal).
- [ ] The bundle asset package and distributable builds are present and internally consistent.
- [ ] The host dependency is pinned to the intended released bundle version.
- [ ] Host cleanup is in a separate, revertible commit.
- [ ] Bundle and host `make ci` pass.
- [ ] Host `make assets-test` passes after package installation and asset build.
- [ ] Browser and accessibility checks pass for the critical workflows.
- [ ] Release notes include the public-template replacement and rollback steps.
