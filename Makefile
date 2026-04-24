SHELL := /bin/sh
.DEFAULT_GOAL := help

include make/config.mk
include make/setup.mk
include make/lifecycle.mk
include make/api.mk
include make/web.mk
include make/tooling.mk
include make/verify.mk

.PHONY: help
help:
	@printf "Leverly local commands\n\n"
	@printf "Setup and lifecycle:\n"
	@printf "  make setup    Prepare local dependencies when apps exist\n"
	@printf "  make up       Start local infrastructure when compose exists\n\n"
	@printf "Development:\n"
	@printf "  make api      Run the API development process\n"
	@printf "  make web      Run the web development process\n\n"
	@printf "Verification:\n"
	@printf "  make test     Run available tests\n"
	@printf "  make lint     Run available lint and format checks\n"
	@printf "  make types    Run available type and contract checks\n"
