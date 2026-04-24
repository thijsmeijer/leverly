.PHONY: up
up:
	@if [ ! -f "$(COMPOSE_FILE)" ]; then \
		printf "Local infrastructure is not available yet.\n"; \
		printf "Expected compose file: %s\n" "$(COMPOSE_FILE)"; \
		printf "Next step: add Docker Compose for PostgreSQL, Redis, and Mailpit.\n"; \
		exit 1; \
	fi
	@HOST_UID="$(HOST_UID)" HOST_GID="$(HOST_GID)" $(DOCKER_COMPOSE) -f "$(COMPOSE_FILE)" up -d
