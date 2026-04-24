SHELL := /bin/sh
.DEFAULT_GOAL := help

include make/config.mk
include make/setup.mk
include make/lifecycle.mk
include make/api.mk
include make/web.mk
include make/tooling.mk
include make/doctor.mk
include make/verify.mk

.PHONY: help
help:
	@printf "Leverly local commands\n\n"
	@printf "Setup and lifecycle:\n"
	@printf "  make setup    Prepare local dependencies when apps exist\n"
	@printf "  make up       Start the full local Docker stack\n"
	@printf "  make services-up  Start only PostgreSQL, Redis, and Mailpit\n"
	@printf "  make down     Stop local containers\n"
	@printf "  make ps       Show local container status\n\n"
	@printf "Diagnostics:\n"
	@printf "  make doctor   Check local setup and print concrete fixes\n\n"
	@printf "Development:\n"
	@printf "  make api      Run the API development process\n"
	@printf "  make web      Run the web development process\n\n"
	@printf "API contract:\n"
	@printf "  make api-openapi  Generate the OpenAPI spec from Laravel\n"
	@printf "  make api-client   Generate the OpenAPI spec and refresh web types\n\n"
	@printf "Verification:\n"
	@printf "  make verify   Run lint, type checks, and tests\n"
	@printf "  make test     Run available tests\n"
	@printf "  make lint     Run available lint and format checks\n"
	@printf "  make types    Run available type and contract checks\n"
