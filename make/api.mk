.PHONY: api api-format-check api-test api-test-unit api-test-api api-test-integration api-test-coverage api-test-unit-coverage api-test-api-coverage api-test-integration-coverage api-openapi api-client contract-check
api:
	@if [ ! -f "$(API_DIR)/artisan" ]; then \
		printf "API app is not scaffolded yet.\n"; \
		printf "Expected Laravel artisan file: %s/artisan\n" "$(API_DIR)"; \
		printf "Next step: scaffold the Laravel API in apps/api.\n"; \
		exit 1; \
	fi
	@cd "$(API_DIR)" && "$(PHP)" artisan serve

api-format-check:
	@if [ ! -f "$(API_DIR)/vendor/bin/pint" ]; then \
		printf "API formatter is not available yet.\n"; \
		printf "Expected Pint binary: %s/vendor/bin/pint\n" "$(API_DIR)"; \
		exit 1; \
	fi
	@cd "$(API_DIR)" && ./vendor/bin/pint --test

api-test: api-test-unit api-test-api api-test-integration

api-test-unit:
	@if [ ! -f "$(API_DIR)/artisan" ]; then \
		printf "API app is not scaffolded yet.\n"; \
		printf "Expected Laravel artisan file: %s/artisan\n" "$(API_DIR)"; \
		exit 1; \
	fi
	@cd "$(API_DIR)" && "$(COMPOSER)" test:unit

api-test-api:
	@if [ ! -f "$(API_DIR)/artisan" ]; then \
		printf "API app is not scaffolded yet.\n"; \
		printf "Expected Laravel artisan file: %s/artisan\n" "$(API_DIR)"; \
		exit 1; \
	fi
	@cd "$(API_DIR)" && "$(COMPOSER)" test:api

api-test-integration:
	@if [ ! -f "$(API_DIR)/artisan" ]; then \
		printf "API app is not scaffolded yet.\n"; \
		printf "Expected Laravel artisan file: %s/artisan\n" "$(API_DIR)"; \
		exit 1; \
	fi
	@cd "$(API_DIR)" && "$(COMPOSER)" test:integration

api-test-coverage: api-test-unit-coverage api-test-api-coverage api-test-integration-coverage

api-test-unit-coverage:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app up -d api
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app exec -T api composer test:unit:coverage

api-test-api-coverage:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app up -d api
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app exec -T api composer test:api:coverage

api-test-integration-coverage:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app up -d api
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app exec -T api composer test:integration:coverage

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

contract-check:
	@if [ ! -f "$(ROOT_DIR)/docs/api/openapi.yaml" ]; then \
		printf "OpenAPI contract is missing at %s/docs/api/openapi.yaml\n" "$(ROOT_DIR)"; \
		exit 1; \
	fi
	@printf "OpenAPI contract exists at %s/docs/api/openapi.yaml\n" "$(ROOT_DIR)"
	@if [ -f "$(WEB_DIR)/package.json" ] && grep -q '"api-client:check"' "$(WEB_DIR)/package.json"; then \
		cd "$(WEB_DIR)" && $(PNPM) api-client:check; \
	else \
		printf "Web API client check skipped: web app or api-client:check script is unavailable.\n"; \
	fi
