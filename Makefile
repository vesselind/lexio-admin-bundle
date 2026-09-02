.PHONY: ci tests quality install assets-install assets-build assets-styles-build assets-test

ifeq ($(OS),Windows_NT)
PHPSTAN := vendor\\bin\\phpstan.bat
PHPUNIT := vendor\\bin\\phpunit.bat
else
PHPSTAN := vendor/bin/phpstan
PHPUNIT := vendor/bin/phpunit
endif

# ─── Install ──────────────────────────────────────────────────────────────────
install:
	composer install

# ─── CI (run all checks) ──────────────────────────────────────────────────────
ci: quality tests

# ─── Static analysis ─────────────────────────────────────────────────────────
quality:
	$(PHPSTAN) analyse src --level=8 --memory-limit=512M

# ─── Tests ───────────────────────────────────────────────────────────────────
tests:
	$(PHPUNIT)

# ─── Frontend package ───────────────────────────────────────────────────────
assets-install:
	yarn --cwd assets install --ignore-scripts

assets-build: assets-styles-build
	node assets/build.mjs

assets-styles-build:
	node assets/build-styles.mjs

assets-test:
	node --test assets/tests/controllers-contract.test.mjs assets/tests/styles-contract.test.mjs

# ─── Quality tooling setup ───────────────────────────────────────────────────
quality-install:
	composer require --dev phpstan/phpstan phpunit/phpunit symfony/phpunit-bridge
	@echo "Quality tooling installed. Run 'make ci' to validate."

