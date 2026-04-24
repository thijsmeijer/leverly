.PHONY: up services-up down restart ps logs compose-config
up:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		printf "Next step: add Docker Compose for PostgreSQL, Redis, and Mailpit.\n"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app up -d --build

services-up:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" up -d postgres redis mailpit

down:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app down

restart: down up

ps:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app ps

logs:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app logs --tail=200

compose-config:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		exit 1; \
	fi
	@$(compose_env) $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" --profile app config
