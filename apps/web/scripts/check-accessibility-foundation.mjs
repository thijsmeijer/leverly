import assert from 'node:assert/strict'
import { existsSync, readFileSync } from 'node:fs'
import { join } from 'node:path'
import { fileURLToPath, URL } from 'node:url'

const root = fileURLToPath(new URL('..', import.meta.url))

const requiredFiles = [
  'e2e/accessibility/assertions.ts',
  'e2e/accessibility/scenarios.ts',
  'e2e/accessibility/scenarioRunner.ts',
  'e2e/tests/accessibility.spec.ts',
]

const packageJson = JSON.parse(readFileSync(join(root, 'package.json'), 'utf8'))
const accessibilitySpec = readFileSync(join(root, 'e2e/tests/accessibility.spec.ts'), 'utf8')

for (const filePath of requiredFiles) {
  assert.equal(existsSync(join(root, filePath)), true, `Missing accessibility foundation path: ${filePath}`)
}

for (const scriptName of ['test:foundation', 'test:a11y']) {
  assert.equal(typeof packageJson.scripts?.[scriptName], 'string', `Missing package script: ${scriptName}`)
}

assert.match(accessibilitySpec, /accessibilityScenarios/)
assert.match(accessibilitySpec, /runAccessibilityScenario/)
assert.match(accessibilitySpec, /@a11y/)
