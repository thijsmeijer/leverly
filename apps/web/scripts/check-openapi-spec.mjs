import { accessSync, constants, readFileSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const currentDir = path.dirname(fileURLToPath(import.meta.url))
const defaultSpecPath = path.resolve(currentDir, '../../../docs/api/openapi.yaml')
const configuredSpecPath = process.env.OPENAPI_SPEC_PATH ?? process.env.LEVERLY_OPENAPI_SPEC_PATH ?? defaultSpecPath
const specPath = path.isAbsolute(configuredSpecPath)
  ? configuredSpecPath
  : path.resolve(process.cwd(), configuredSpecPath)

try {
  accessSync(specPath, constants.R_OK)
  const contents = readFileSync(specPath, 'utf8')

  if (!contents.includes('openapi:')) {
    throw new Error('The file does not look like an OpenAPI document.')
  }

  console.log(`OpenAPI source is readable: ${specPath}`)
} catch (error) {
  const detail = error instanceof Error ? error.message : String(error)

  console.error(`OpenAPI source is not readable: ${specPath}`)
  console.error(detail)
  process.exit(1)
}
