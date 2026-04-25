.PHONY: web web-type-check web-lint web-format-check web-test-foundation web-test-unit web-test-architecture web-test-a11y-foundation web-test-a11y web-test-e2e
web:
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		printf "Next step: scaffold the Vue app in apps/web.\n"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) dev

web-type-check:
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) type-check

web-lint:
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) lint

web-format-check:
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) format:test

web-test-foundation:
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) test:foundation

web-test-unit:
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) test:unit

web-test-architecture:
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) test:architecture

web-test-a11y-foundation:
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) test:a11y:foundation

web-test-a11y:
	@if [ ! -d "$(WEB_DIR)/e2e/tests" ]; then \
		printf "Web accessibility tests skipped: no E2E test directory exists yet.\n"; \
	elif grep -R "@a11y" "$(WEB_DIR)/e2e/tests" >/dev/null 2>&1; then \
		cd "$(WEB_DIR)" && $(PNPM) test:a11y; \
	else \
		printf "Web accessibility tests skipped: no @a11y tests exist yet.\n"; \
	fi

web-test-e2e:
	@if [ ! -d "$(WEB_DIR)/e2e/tests" ]; then \
		printf "Web E2E tests skipped: no E2E test directory exists yet.\n"; \
	elif find "$(WEB_DIR)/e2e/tests" -name "*.spec.ts" -type f -exec grep -L "@a11y" {} \; | grep . >/dev/null 2>&1; then \
		cd "$(WEB_DIR)" && $(PNPM) test:e2e -- --grep-invert @a11y; \
	else \
		printf "Web E2E tests skipped: no non-accessibility E2E tests exist yet.\n"; \
	fi
