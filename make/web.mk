.PHONY: web
web:
	@if [ ! -f "$(WEB_DIR)/package.json" ]; then \
		printf "Web app is not scaffolded yet.\n"; \
		printf "Expected package file: %s/package.json\n" "$(WEB_DIR)"; \
		printf "Next step: scaffold the Vue app in apps/web.\n"; \
		exit 1; \
	fi
	@cd "$(WEB_DIR)" && $(PNPM) dev
