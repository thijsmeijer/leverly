.PHONY: setup
setup:
	@printf "Preparing Leverly workspace with UID=%s GID=%s\n" "$(HOST_UID)" "$(HOST_GID)"
	@if [ -f "$(API_DIR)/composer.json" ]; then \
		printf "Installing API dependencies in %s\n" "$(API_DIR)"; \
		cd "$(API_DIR)" && "$(COMPOSER)" install; \
	else \
		printf "API dependencies skipped: %s/composer.json does not exist yet.\n" "$(API_DIR)"; \
	fi
	@if [ -f "$(WEB_DIR)/package.json" ]; then \
		printf "Installing web dependencies in %s\n" "$(WEB_DIR)"; \
		cd "$(WEB_DIR)" && "$(PNPM)" install; \
	else \
		printf "Web dependencies skipped: %s/package.json does not exist yet.\n" "$(WEB_DIR)"; \
	fi
