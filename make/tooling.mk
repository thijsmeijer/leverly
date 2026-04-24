.PHONY: tools-info
tools-info:
	@printf "Configured paths:\n"
	@printf "  API_DIR=%s\n" "$(API_DIR)"
	@printf "  WEB_DIR=%s\n" "$(WEB_DIR)"
	@printf "  PACKAGES_DIR=%s\n" "$(PACKAGES_DIR)"
	@printf "  INFRA_DIR=%s\n" "$(INFRA_DIR)"
	@printf "  HOST_UID=%s\n" "$(HOST_UID)"
	@printf "  HOST_GID=%s\n" "$(HOST_GID)"
