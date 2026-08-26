.PHONY: ci tests quality install

# ─── Install ──────────────────────────────────────────────────────────────────
install:
	composer install

# ─── CI (run all checks) ──────────────────────────────────────────────────────
ci: quality tests

# ─── Static analysis ─────────────────────────────────────────────────────────
quality:
	vendor/bin/phpstan analyse src --level=8 --memory-limit=512M

# ─── Tests ───────────────────────────────────────────────────────────────────
tests:
	vendor/bin/phpunit

# ─── Quality tooling setup ───────────────────────────────────────────────────
quality-install:
	composer require --dev phpstan/phpstan phpunit/phpunit symfony/phpunit-bridge
	@echo "Quality tooling installed. Run 'make ci' to validate."

