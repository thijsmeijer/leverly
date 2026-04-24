SHELL := /bin/sh
.DEFAULT_GOAL := help

include make/config.mk
include make/setup.mk
include make/lifecycle.mk
include make/api.mk
include make/web.mk
include make/modules.mk
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
	@printf "Module scaffolds:\n"
	@printf "  make api-module MODULE=training        Create an API domain scaffold\n"
	@printf "  make web-module MODULE=workouts        Create a web module scaffold\n"
	@printf "  make modules-check                     Validate web module structure\n"
	@printf "  Add DRY_RUN=1 to preview scaffold output without writing files\n\n"
	@printf "API contract:\n"
	@printf "  make api-openapi  Generate the OpenAPI spec from Laravel\n"
	@printf "  make api-client   Generate the OpenAPI spec and refresh web types\n\n"
	@printf "Verification:\n"
	@printf "  make verify   Run the full layered verification suite\n"
	@printf "  make test     Run API, web unit, architecture, accessibility, and E2E tests\n"
	@printf "  make lint     Run API format, web lint, and web format checks\n"
	@printf "  make types    Run contract and web type checks\n"
	@printf "  make api-test            Run all API test suites\n"
	@printf "  make api-test-unit       Run API unit tests\n"
	@printf "  make api-test-api        Run API HTTP tests\n"
	@printf "  make api-test-integration  Run API integration tests\n"
	@printf "  make api-test-coverage   Run all API coverage checks with PCOV\n"
	@printf "  make api-format-check    Run API formatter check\n"
	@printf "  make contract-check      Check the OpenAPI contract and web client\n"
	@printf "  make web-type-check      Run web type checks\n"
	@printf "  make web-lint            Run web lint\n"
	@printf "  make web-format-check    Run web format check\n"
	@printf "  make web-test-unit       Run web unit tests\n"
	@printf "  make web-test-architecture  Run web architecture tests\n"
	@printf "  make web-test-a11y       Run web accessibility tests\n"
	@printf "  make web-test-e2e        Run web end-to-end tests\n"
