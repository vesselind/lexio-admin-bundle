# Feature: Reusable SSH Production Deployment Command

## Context
**Issue Tracking:** None

The bundle should provide a reusable `prod:deploy` Symfony console command for host applications. The existing command in `D:\laragon\www\exlege5` is only the behavioral reference; its application-specific constants must be removed from the bundle implementation. When enabled and configured, the command connects over SSH and runs `git pull` followed by the configured deployment script on the remote host. Deployment is opt-in and must not store or request passwords in bundle configuration. The `lexio-admin` starter application must receive `docs/Documentation/Deployment.md` explaining prerequisites, configuration, and use.

Deployment is disabled by default. Host-specific values (`host`, `user`, and `remote_path`) default to `null` and must be configured when deployment is enabled; `port` defaults to `22`, `deploy_script` to `scripts/dev_next_deploy.sh`, and `timeout`/`identity_file` to `null`. Every value must be configurable by the consuming application.

## User Stories
- As a host application developer, I want to run `prod:deploy` so that I can deploy the application through the existing remote Git and shell workflow.
- As a bundle consumer, I want to configure the SSH connection and remote paths so that the same command works for different environments and servers.
- As an operator, I want live remote output and a reliable exit status so that I can monitor deployment and use the command in scripts.
- As a security-conscious operator, I want SSH keys or the local SSH agent to provide authentication so that no password or private-key material is stored in configuration.
- As a `lexio-admin` maintainer, I want a deployment guide so that the starter application can be configured and operated consistently.

## Acceptance Criteria (Gherkin)
Given deployment is enabled and all required connection settings are valid
When the operator runs `prod:deploy`
Then the command connects to the configured SSH user, host, and port
And executes `cd <remote_path> && git pull && bash <deploy_script>`
And streams the remote process output
And returns a success exit code only when the complete sequence succeeds

Given a custom deployment script is configured
When `prod:deploy` runs
Then it uses that script path while retaining the `git pull`-before-script order

Given `git pull` or the deployment script exits unsuccessfully
When the remote process finishes
Then the command reports failure and returns a non-zero exit code
And the later command in the `&&` sequence is not run

Given deployment is disabled or required settings are missing or invalid
When the operator runs `prod:deploy`
Then the command returns a non-zero exit code with actionable configuration guidance
And no SSH connection is attempted

Given an optional identity file is configured
When the command connects
Then SSH uses that file without exposing its contents in output
And when it is not configured, the local SSH defaults or agent are used

Given the SSH connection, authentication, or host-key verification fails
When the command runs
Then it returns a non-zero exit code, reports the failure, and does not claim deployment success
And host-key verification is not bypassed automatically

Given a configured remote path or script path contains spaces or shell-significant characters
When the command builds the remote operation
Then each path is safely quoted as a path value
And configuration cannot inject an additional remote command

Given a finite deployment timeout is configured
When the deployment exceeds that timeout
Then the process is stopped and the command returns a non-zero exit code
And when no timeout is configured, deployment retains the reference behavior of having no time limit

Given an operator follows the starter application setup
When they read `D:\laragon\www\lexio-admin\docs\Documentation\Deployment.md`
Then the guide documents SSH prerequisites, key/agent setup, bundle configuration, command invocation, expected success/failure behavior, and safe secret handling

## Edge Cases
- Empty or whitespace-only required values: reject configuration before connecting.
- Unreachable host, DNS failure, refused connection, authentication failure, or remote non-zero exit: preserve useful SSH/process diagnostics, return failure, and never print passwords or private-key contents.
- Unknown or changed host key: defer to normal SSH verification and fail safely; never use an automatic trust or verification-bypass option.
- IPv4, IPv6, non-default ports, and identity paths with spaces: support valid SSH inputs.
- Remote path or script path traversal, control characters, or malformed values: reject or safely quote them; never interpret them as arbitrary command fragments.
- `git pull` succeeds but the script fails: report deployment failure; do not report partial success or attempt rollback.
- A missing executable, interrupted process, or local permission error: return a non-zero status with an actionable error.
- Concurrent invocations, branch selection, and remote uncommitted changes: do not add coordination or automatic remediation; surface the remote command result.

## Out of Scope
- Password authentication, password prompts managed by the bundle, private-key generation/storage, or automatic known-hosts management.
- Uploading files, rsync, selecting commits/branches, migrations, health checks, rollback, or deployment orchestration beyond the `git pull` and deployment-script sequence.
- Multi-host or parallel deployments, scheduling, CI-provider integration, notifications, and interactive approval workflows.
- Changing the `exlege5` reference command, its remote deployment script, or server configuration.
- Reusing translation-management HTTP settings as SSH deployment settings.

## Technical Notes
- The command is a public bundle capability; host-specific values formerly held as constants in the `exlege5` reference command must be configuration-driven. Expected configuration covers `enabled`, host, user, port, remote path, deployment script, optional identity file, and optional timeout.
- Deployment defaults are `enabled: false`, `host: null`, `user: null`, `port: 22`, `remote_path: null`, `deploy_script: scripts/dev_next_deploy.sh`, `identity_file: null`, and `timeout: null`. The remote command default remains `git pull && bash <deploy_script>` after changing directory.
- Existing `translation_management.synchronization` values such as deployed HTTP URL, Basic Auth, and HTTP timeout are semantically unrelated and must not be silently reused. The local translation directory is also not the remote deployment path.
- SSH should use the host application’s normal client/agent and known-hosts behavior. No plaintext secret belongs in bundle configuration or diagnostic output.
- The architect should define validation and service boundaries so the command can be tested without a live server. Coverage should include configuration processing, success, each failure stage, timeout, disabled/unconfigured operation, identity-file handling, and remote-command quoting.
- The `lexio-admin` starter app guide at `D:\laragon\www\lexio-admin\docs\Documentation\Deployment.md` is a host-application deliverable and must show environment-safe configuration, preferably referencing an existing environment variable for the identity-file path rather than embedding credentials. The `exlege5` project is reference-only and must not be modified as part of this feature.
