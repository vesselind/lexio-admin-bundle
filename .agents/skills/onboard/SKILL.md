---
name: onboard
description: Profile a Symfony bundle repository and generate factual Project DNA guidance in AGENTS.md. Use when a user explicitly requests onboarding or types /onboard.
---

# Onboard — Bundle Profiling

> Profile a Symfony bundle package (composer), generate Project DNA in AGENTS.md, adapt rules.
> **Invoke when:** user explicitly types `/onboard`. Never auto-apply.

Profile a **Symfony bundle** repository: `composer.json` (type, require, suggest), `src/`, `config/`, tests layout. Generate factual Project DNA in **AGENTS.md**. Adapt to what IS (AbstractBundle vs legacy Extension, XML services, optional Doctrine/UX).

## When to Use

- User explicitly types `/onboard` or requests project onboarding
- User wants to adapt rules to an existing Symfony bundle package
- Do NOT use when: user asks for a feature, bug fix, or quality setup (use the relevant skill)

## Instructions

### Step 1: Detect bundle root

Use the directory containing the bundle's `composer.json` (package name usually `vendor/*-bundle`). If the workspace is monorepo, identify the bundle subfolder. If unclear, ask the user for the bundle root path.

### Step 2: Read composer.json — Extract Project Metadata

Read the project's `composer.json` and extract:
- Symfony version (from `require` or `symfony/framework-bundle`)
- Installed components relevant to boilerplate rules (see mapping below)
- PHP version constraint
- Dev dependencies for quality tooling

**Rule-to-composer mapping (for Step 5):**
- `coding-standards.md`, `architecture.md`, `quality-pipeline.md`, `dto.md`, `error-handling.md`, `api.md` → no specific package (always applicable)
- `console-commands.md` → `symfony/console` (always present)
- `doctrine.md` → `doctrine/orm` (typically present)
- `testing.md` → `phpunit/phpunit` or `symfony/phpunit-bridge` (typically present)
- `messenger.md` → `symfony/messenger`
- `api-platform.md` → `api-platform/core` or `api-platform/api-platform`
- `security.md` → `symfony/security-bundle`
- `forms.md` → `symfony/form`
- `frontend.md` → `symfony/stimulus-bundle` or `symfony/asset-mapper`
- `twig.md` → `symfony/twig-bundle`
- `i18n.md` → `symfony/translation`
- `observability.md` → `symfony/monolog-bundle`
- `http-client.md` → `symfony/http-client`
- `caching.md` → `symfony/cache`
- `workflow.md` → `symfony/workflow`
- `mailer.md` → `symfony/mailer`
- `serializer.md` → `symfony/serializer` (typically present)
- `validator.md` → `symfony/validator` (typically present)

### Step 3: Explore the Codebase in Parallel

Explore three aspects of the codebase simultaneously (use subagents or sequential exploration):

**Area 1 — Infrastructure audit:**
- Config files (framework, services, routing)
- Makefile presence and targets
- CI configuration (GitHub Actions, GitLab CI, etc.)
- `.env` structure and environment variables
- Return: infrastructure fingerprint

**Area 2 — Source code audit:**
- Directory structure (`src/`, namespaces, bounded contexts if any)
- Controller patterns (annotation vs attribute, HTTP layer organization)
- Entity design (rich vs anemic, repository pattern)
- DTO organization and mapping approach
- Return: source architecture fingerprint

**Area 3 — Test audit:**
- Test framework and configuration
- Fixture strategy (Doctrine, custom, factories)
- Coverage tooling and thresholds
- Test directory structure
- Return: test architecture fingerprint

### Step 4: Interactive Questioning — Rules Without Matching Dependencies

For each boilerplate rule that does NOT have a matching composer dependency in the project:
- Ask the user: "Messenger is not in your composer.json. Keep the messenger rule for future use?"
- Options: keep (rule stays in index, user may add dependency later) or remove (exclude from Project DNA recommendations)
- Record user choices for the Project DNA section

### Step 5: Generate Project DNA

Write the `## Project DNA` section in the repository-root `AGENTS.md`. Replace the placeholder with a factual architecture fingerprint.

**Structure:**
- **Symfony version:** X.Y
- **Infrastructure:** (Makefile targets found, CI tool, config layout)
- **Source patterns:** (controller style, entity style, repository pattern, DTO location)
- **Test patterns:** (framework, fixtures, coverage)
- **Applicable rules:** (list rules to keep based on Step 4 choices and composer deps)

Do NOT use labels like "DDD" or "MVC" unless the code explicitly shows those patterns. Use neutral descriptions: "Entities in src/Entity with repositories in src/Repository" etc.

### Step 6: Detect or Create Makefile Targets (3 targets only)

**Contract:** Only three targets are required: `make quality`, `make tests`, `make ci`. All tools (PHPStan, Deptrac, PHPUnit, Infection, lint, etc.) run inside these. See `../../rules/quality-pipeline.md` and the `quality-install` skill templates for reference.

**Makefile-solution:** If the project has a root Makefile that `include`s `Makefile-solution`, add or edit these three targets in **Makefile-solution** only, not in the root Makefile.

Ensure the following targets exist (in the project's Makefile or in Makefile-solution if that pattern exists):
- `make quality` — lint, Deptrac, PHPStan, CS check (all quality tools)
- `make tests` — PHPUnit, Infection (all test tools)
- `make ci` — full pipeline (quality + tests; optionally composer audit)

**Adaptation for existing projects:** If the project already has a Makefile with different target names or tools, map them to these three (aliases are fine). If the project uses Docker, wrap commands with the project's container exec prefix.

## Exit Checklist (MANDATORY before concluding)

- [ ] Symfony root detected and documented
- [ ] composer.json read — Symfony version and components extracted
- [ ] Three codebase areas explored (infrastructure, source, test)
- [ ] User answered interactive questions for rules without matching composer deps
- [ ] Project DNA written to the repository-root `AGENTS.md` with factual fingerprint (no prescribed labels)
- [ ] Makefile (or Makefile-solution if used) has `make quality`, `make tests`, `make ci`

## On Failure

- If no composer.json found: ask user for project path, retry detection
- If exploration returns empty or errors: run a second pass with narrower scope, or proceed with partial data and note "partial audit" in Project DNA
- If user declines all optional rules: respect choice, list only core rules (coding-standards, architecture, quality-pipeline) in Project DNA
- If Makefile creation fails (e.g. no Make in PATH): document required targets for user to add manually
- If stuck after 3 attempts: ask the user for guidance

