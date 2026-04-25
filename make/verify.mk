.PHONY: verify test lint types quality-gates-test
verify: api-format-check api-test module-scaffold-test modules-check quality-gates-test contract-check web-type-check web-lint web-format-check web-test-foundation web-test-unit web-test-architecture web-test-a11y web-test-e2e

test: api-test web-test-foundation web-test-unit web-test-architecture web-test-a11y web-test-e2e

lint: api-format-check web-lint web-format-check

types: contract-check web-type-check

quality-gates-test:
	@node --test "$(ROOT_DIR)/scripts/quality-gates.test.mjs"
