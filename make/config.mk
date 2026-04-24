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

LEVERLY_BIND_IP ?= 127.0.0.1
LEVERLY_API_PORT ?= 8000
LEVERLY_WEB_PORT ?= 5173
LEVERLY_POSTGRES_PORT ?= 5432
LEVERLY_REDIS_PORT ?= 6379
LEVERLY_MAILPIT_SMTP_PORT ?= 1025
LEVERLY_MAILPIT_UI_PORT ?= 8025

HOST_UID ?= $(shell id -u 2>/dev/null || printf "1000")
HOST_GID ?= $(shell id -g 2>/dev/null || printf "1000")

define compose_env
	HOST_UID="$(HOST_UID)" \
	HOST_GID="$(HOST_GID)" \
	LEVERLY_BIND_IP="$(LEVERLY_BIND_IP)" \
	LEVERLY_API_PORT="$(LEVERLY_API_PORT)" \
	LEVERLY_WEB_PORT="$(LEVERLY_WEB_PORT)" \
	LEVERLY_POSTGRES_PORT="$(LEVERLY_POSTGRES_PORT)" \
	LEVERLY_REDIS_PORT="$(LEVERLY_REDIS_PORT)" \
	LEVERLY_MAILPIT_SMTP_PORT="$(LEVERLY_MAILPIT_SMTP_PORT)" \
	LEVERLY_MAILPIT_UI_PORT="$(LEVERLY_MAILPIT_UI_PORT)"
endef

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
