.PHONY: api-module web-module modules-check module-scaffold-test

api-module:
	@node "$(ROOT_DIR)/scripts/scaffold-module.mjs" api --root "$(ROOT_DIR)" --module "$(MODULE)" $(if $(DRY_RUN),--dry-run,)

web-module:
	@node "$(ROOT_DIR)/scripts/scaffold-module.mjs" web --root "$(ROOT_DIR)" --module "$(MODULE)" $(if $(DRY_RUN),--dry-run,)

modules-check:
	@node "$(ROOT_DIR)/scripts/scaffold-module.mjs" check-web --root "$(ROOT_DIR)"

module-scaffold-test:
	@node --test "$(ROOT_DIR)/scripts/scaffold-module.test.mjs"
