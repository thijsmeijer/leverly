.PHONY: api
api:
	@if [ ! -f "$(API_DIR)/artisan" ]; then \
		printf "API app is not scaffolded yet.\n"; \
		printf "Expected Laravel artisan file: %s/artisan\n" "$(API_DIR)"; \
		printf "Next step: scaffold the Laravel API in apps/api.\n"; \
		exit 1; \
	fi
	@cd "$(API_DIR)" && "$(PHP)" artisan serve

.PHONY: mail-smoke
mail-smoke:
	@if [ ! -f "$(API_DIR)/artisan" ]; then \
		printf "API app is not scaffolded yet.\n"; \
		printf "Expected Laravel artisan file: %s/artisan\n" "$(API_DIR)"; \
		printf "Next step: scaffold the Laravel API in apps/api.\n"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app exec -T api php artisan leverly:mail-smoke --to="$(MAIL_SMOKE_TO)"
