import assert from 'node:assert/strict'
import { existsSync, readdirSync, readFileSync } from 'node:fs'
import { join } from 'node:path'
import { fileURLToPath, URL } from 'node:url'

const root = fileURLToPath(new URL('..', import.meta.url))

const requiredFiles = ['src/tests/setupVitest.ts', 'src/tests/harness.ts', 'src/tests/http.ts', 'e2e/tests']

const packageJson = JSON.parse(readFileSync(join(root, 'package.json'), 'utf8'))
const vitestConfig = readFileSync(join(root, 'vitest.config.ts'), 'utf8')
const playwrightConfig = readFileSync(join(root, 'playwright.config.ts'), 'utf8')

for (const filePath of requiredFiles) {
  assert.equal(existsSync(join(root, filePath)), true, `Missing frontend test foundation path: ${filePath}`)
}

for (const scriptName of [
  'test',
  'test:foundation',
  'test:unit',
  'test:architecture',
  'test:a11y',
  'test:a11y:foundation',
  'test:e2e',
]) {
  assert.equal(typeof packageJson.scripts?.[scriptName], 'string', `Missing package script: ${scriptName}`)
}

assert.match(vitestConfig, /setupFiles:\s*\['\.\/src\/tests\/setupVitest\.ts'\]/)
assert.match(vitestConfig, /include:\s*\['src\/\*\*\/\*\.spec\.ts', 'scripts\/\*\*\/\*\.test\.mjs'\]/)
assert.match(playwrightConfig, /name:\s*'chromium'/)
assert.match(playwrightConfig, /name:\s*'mobile'/)

const sourceFiles = listFiles(join(root, 'src'))
const snapshotFiles = sourceFiles.filter((filePath) => filePath.endsWith('.snap'))
const snapshotAssertions = sourceFiles.filter((filePath) => {
  if (!/\.(ts|vue)$/.test(filePath)) {
    return false
  }

  return readFileSync(filePath, 'utf8').includes('toMatchSnapshot')
})

assert.deepEqual(snapshotFiles, [], 'Snapshot files are not part of the frontend behavior test foundation')
assert.deepEqual(snapshotAssertions, [], 'Snapshot assertions are not part of the frontend behavior test foundation')

function listFiles(directory) {
  return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const path = join(directory, entry.name)

    return entry.isDirectory() ? listFiles(path) : [path]
  })
}
