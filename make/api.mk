.PHONY: api api-openapi api-client
api:
	@if [ ! -f "$(API_DIR)/artisan" ]; then \
		printf "API app is not scaffolded yet.\n"; \
		printf "Expected Laravel artisan file: %s/artisan\n" "$(API_DIR)"; \
		printf "Next step: scaffold the Laravel API in apps/api.\n"; \
		exit 1; \
	fi
	@cd "$(API_DIR)" && "$(PHP)" artisan serve

api-openapi:
	@if [ ! -f "$(API_DIR)/artisan" ]; then \
		printf "API app is not scaffolded yet.\n"; \
		printf "Expected Laravel artisan file: %s/artisan\n" "$(API_DIR)"; \
		printf "Next step: scaffold the Laravel API in apps/api.\n"; \
		exit 1; \
	fi
	@cd "$(API_DIR)" && "$(COMPOSER)" openapi

api-client: api-openapi
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		printf "Next step: scaffold the Vue app in apps/web.\n"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) api-client:generate
	@cd "$(WEB_DIR)" && $(PNPM) api-client:check
