ROOT_DIR := $(CURDIR)
API_DIR := $(ROOT_DIR)/apps/api
WEB_DIR := $(ROOT_DIR)/apps/web
PACKAGES_DIR := $(ROOT_DIR)/packages
INFRA_DIR := $(ROOT_DIR)/infra
COMPOSE_FILE := $(INFRA_DIR)/docker-compose.yml

DOCKER_COMPOSE ?= docker compose
PNPM ?= corepack pnpm
COMPOSER ?= composer
PHP ?= php

HOST_UID ?= $(shell id -u 2>/dev/null || printf "1000")
HOST_GID ?= $(shell id -g 2>/dev/null || printf "1000")

define missing_file
	@printf "Missing %s at %s\n" "$(1)" "$(2)"
	@printf "Next step: %s\n" "$(3)"
	@exit 1
endef

define missing_dir
	@printf "Missing %s directory at %s\n" "$(1)" "$(2)"
	@printf "Next step: %s\n" "$(3)"
	@exit 1
endef

define unavailable
	@printf "%s is not available yet.\n" "$(1)"
	@printf "Next step: %s\n" "$(2)"
	@exit 1
endef
