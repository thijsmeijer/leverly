import assert from 'node:assert/strict'
import { existsSync, readFileSync } from 'node:fs'
import { join } from 'node:path'
import { fileURLToPath, URL } from 'node:url'

const root = fileURLToPath(new URL('..', import.meta.url))

const requiredFiles = [
  'src/shared/ui/UiBadge.vue',
  'src/shared/ui/UiButton.vue',
  'src/shared/ui/UiCard.vue',
  'src/shared/ui/UiProgress.vue',
  'src/shared/ui/UiSectionHeader.vue',
  'src/shared/ui/index.ts',
  '../../docs/development/design-system.md',
]

const style = readFileSync(join(root, 'src/style.css'), 'utf8')
const packageJson = JSON.parse(readFileSync(join(root, 'package.json'), 'utf8'))

for (const filePath of requiredFiles) {
  assert.equal(existsSync(join(root, filePath)), true, `Missing design system file: ${filePath}`)
}

for (const token of [
  '--color-surface-primary',
  '--color-surface-elevated',
  '--color-ink-primary',
  '--color-accent-primary',
  '--color-status-success',
  '--radius-card',
  '--shadow-card',
]) {
  assert.match(style, new RegExp(`${escapeRegExp(token)}:`), `Missing design token: ${token}`)
}

assert.match(style, /prefers-color-scheme:\s*dark/, 'Missing system dark mode support')
assert.match(style, /\[data-theme='dark'\]/, 'Missing explicit dark theme support')
assert.match(style, /min-height:\s*44px/, 'Interactive controls need a minimum touch target')
assert.match(style, /focus-visible/, 'Design system needs focus-visible styling')

assert.equal(typeof packageJson.scripts?.['test:design-system'], 'string', 'Missing package script: test:design-system')
assert.match(packageJson.scripts?.['test:foundation'], /check-design-system\.mjs/)

function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}
