.PHONY: api
api:
	@if [ ! -f "$(API_DIR)/artisan" ]; then \
		printf "API app is not scaffolded yet.\n"; \
		printf "Expected Laravel artisan file: %s/artisan\n" "$(API_DIR)"; \
		printf "Next step: scaffold the Laravel API in apps/api.\n"; \
		exit 1; \
	fi
	@cd "$(API_DIR)" && "$(PHP)" artisan serve
