import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { execFileSync } from 'node:child_process'
import test from 'node:test'

const expectedTargets = [
  'api-format-check',
  'api-test',
  'api-test-unit',
  'api-test-api',
  'api-test-integration',
  'api-test-coverage',
  'quality-gates-test',
  'contract-check',
  'modules-check',
  'web-type-check',
  'web-lint',
  'web-format-check',
  'web-test-foundation',
  'web-test-unit',
  'web-test-architecture',
  'web-test-a11y',
  'web-test-e2e',
]

function makeDryRun(target) {
  return execFileSync('make', ['--dry-run', target], {
    encoding: 'utf8',
  })
}

test('focused quality gate make targets are available', () => {
  for (const target of expectedTargets) {
    assert.doesNotThrow(() => makeDryRun(target), target)
  }
})

test('make verify declares the full layered gate order', () => {
  const verify = readFileSync('make/verify.mk', 'utf8')

  assert.match(
    verify,
    /verify: api-format-check api-test module-scaffold-test modules-check quality-gates-test contract-check web-type-check web-lint web-format-check web-test-foundation web-test-unit web-test-architecture web-test-a11y web-test-e2e/,
  )
})

test('make help documents focused gates and full verification', () => {
  const help = execFileSync('make', ['help'], {
    encoding: 'utf8',
  })

  assert.match(help, /make verify\s+Run the full layered verification suite/)
  assert.match(help, /make api-test\s+Run all API test suites/)
  assert.match(help, /make api-test-coverage\s+Run all API coverage checks with PCOV/)
  assert.match(help, /make quality-gates-test\s+Run root verification wiring tests/)
  assert.match(help, /make web-test-foundation\s+Run web test foundation checks/)
  assert.match(help, /make web-test-unit\s+Run web unit tests/)
  assert.match(help, /make web-test-a11y\s+Run web accessibility tests/)
  assert.match(help, /make web-test-e2e\s+Run web end-to-end tests/)
})

test('tooling docs explain focused checks versus full verification', () => {
  const docs = readFileSync('docs/development/tooling.md', 'utf8')

  assert.match(docs, /Focused Checks/)
  assert.match(docs, /Full Verification/)
  assert.match(docs, /make verify/)
})

test('local API container keeps Docker runtime env available to HTTP requests', () => {
  const compose = readFileSync('infra/docker-compose.yml', 'utf8')

  assert.doesNotMatch(compose, /php",\s*"artisan",\s*"serve/)
  assert.match(compose, /cd public && exec php -S 0\.0\.0\.0:8000/)
  assert.match(compose, /server\.php/)
  assert.match(compose, /APP_ENV:\s*local/)
  assert.match(compose, /TELESCOPE_ENABLED:/)
  assert.match(compose, /SENTRY_LARAVEL_DSN:/)
})
