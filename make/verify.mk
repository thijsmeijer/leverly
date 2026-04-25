.PHONY: verify test lint types
verify: api-format-check api-test module-scaffold-test modules-check contract-check web-type-check web-lint web-format-check web-test-foundation web-test-unit web-test-architecture web-test-a11y-foundation web-test-a11y web-test-e2e

test: api-test web-test-foundation web-test-unit web-test-architecture web-test-a11y-foundation web-test-a11y web-test-e2e

lint: api-format-check web-lint web-format-check

types: contract-check web-type-check
