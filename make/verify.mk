.PHONY: test lint types
test:
	@ran=0; \
	if [ -f "$(API_DIR)/artisan" ]; then \
		ran=1; \
		cd "$(API_DIR)" && "$(PHP)" artisan test; \
	fi; \
	if [ -f "$(WEB_DIR)/package.json" ]; then \
		ran=1; \
		cd "$(WEB_DIR)" && "$(PNPM)" test; \
	fi; \
	if [ "$$ran" = "0" ]; then \
		printf "No test suites are available yet.\n"; \
		printf "Expected future locations: %s and %s\n" "$(API_DIR)" "$(WEB_DIR)"; \
		exit 1; \
	fi

lint:
	@ran=0; \
	if [ -f "$(API_DIR)/vendor/bin/pint" ]; then \
		ran=1; \
		cd "$(API_DIR)" && ./vendor/bin/pint --test; \
	fi; \
	if [ -f "$(WEB_DIR)/package.json" ]; then \
		ran=1; \
		cd "$(WEB_DIR)" && "$(PNPM)" lint; \
	fi; \
	if [ "$$ran" = "0" ]; then \
		printf "No lint commands are available yet.\n"; \
		printf "Next step: add API and web scaffolds with their formatter and lint scripts.\n"; \
		exit 1; \
	fi

types:
	@ran=0; \
	if [ -f "$(WEB_DIR)/package.json" ]; then \
		ran=1; \
		cd "$(WEB_DIR)" && "$(PNPM)" type-check; \
	fi; \
	if [ -f "$(ROOT_DIR)/docs/api/openapi.yaml" ]; then \
		ran=1; \
		printf "OpenAPI placeholder exists at %s/docs/api/openapi.yaml\n" "$(ROOT_DIR)"; \
	fi; \
	if [ "$$ran" = "0" ]; then \
		printf "No type or contract checks are available yet.\n"; \
		printf "Next step: scaffold the web app and API client generation.\n"; \
		exit 1; \
	fi
