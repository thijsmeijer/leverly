import assert from 'node:assert/strict'
import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs'
import { join } from 'node:path'
import { fileURLToPath, URL } from 'node:url'

const root = fileURLToPath(new URL('..', import.meta.url))
const sourceRoot = join(root, 'src')

const requiredFiles = [
  'src/shared/brand/leverlyBrand.ts',
  'src/shared/brand/leverlyCopy.ts',
  'src/shared/brand/leverlyCopy.spec.ts',
  '../../docs/development/brand-copy.md',
]

const packageJson = JSON.parse(readFileSync(join(root, 'package.json'), 'utf8'))
const sourceFiles = listFiles(sourceRoot).filter(
  (filePath) =>
    /\.(ts|vue)$/.test(filePath) &&
    !filePath.includes('/openapi/generated.ts') &&
    !filePath.endsWith('.spec.ts') &&
    !filePath.endsWith('.test.ts'),
)

for (const filePath of requiredFiles) {
  assert.equal(existsSync(join(root, filePath)), true, `Missing brand copy file: ${filePath}`)
}

const brand = readFileSync(join(root, 'src/shared/brand/leverlyBrand.ts'), 'utf8')
const copy = readFileSync(join(root, 'src/shared/brand/leverlyCopy.ts'), 'utf8')
const allUserCopySources = sourceFiles.map((filePath) => readFileSync(filePath, 'utf8')).join('\n')

assert.match(brand, /productName:\s*'Leverly'/, 'Brand metadata must define the user-facing product name')
assert.match(
  brand,
  /tagline:\s*'intelligent progression for bodyweight strength\.'/,
  'Brand metadata must define the approved tagline',
)
assert.doesNotMatch(allUserCopySources, /Premium training lab/i, 'Remove stale pre-brand dashboard copy')
assert.doesNotMatch(
  copy,
  /\b(diagnos(?:e|is)|cure|treatment|prescribe|healed)\b/i,
  'Coaching copy must avoid diagnosis or treatment language',
)

for (const requiredPhrase of ['calm', 'actionable', 'precise']) {
  assert.match(copy, new RegExp(requiredPhrase, 'i'), `Copy tone rules should include: ${requiredPhrase}`)
}

assert.equal(typeof packageJson.scripts?.['test:brand-copy'], 'string', 'Missing package script: test:brand-copy')
assert.match(packageJson.scripts?.['test:foundation'], /check-brand-copy\.mjs/)

function listFiles(directory) {
  return readdirSync(directory).flatMap((entry) => {
    const entryPath = join(directory, entry)

    if (statSync(entryPath).isDirectory()) {
      return listFiles(entryPath)
    }

    return entryPath
  })
}
