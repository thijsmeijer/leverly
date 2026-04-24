import { accessSync, constants, mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const currentDir = path.dirname(fileURLToPath(import.meta.url))

export const generatedTypesRelativePath = 'src/shared/api/leverlyApi/openapi/generated.ts'

export function resolveSpecPath({
  currentWorkingDirectory = process.cwd(),
  environment = process.env,
  scriptDirectory = currentDir,
} = {}) {
  const configuredSpecPath = environment.OPENAPI_SPEC_PATH ?? environment.LEVERLY_OPENAPI_SPEC_PATH
  const specPath = configuredSpecPath ?? path.resolve(scriptDirectory, '../../../docs/api/openapi.yaml')

  return path.isAbsolute(specPath) ? specPath : path.resolve(currentWorkingDirectory, specPath)
}

export function resolveGeneratedTypesPath({ currentWorkingDirectory = process.cwd(), environment = process.env } = {}) {
  const configuredOutputPath = environment.LEVERLY_OPENAPI_TYPES_PATH ?? generatedTypesRelativePath

  return path.isAbsolute(configuredOutputPath)
    ? configuredOutputPath
    : path.resolve(currentWorkingDirectory, configuredOutputPath)
}

export function readOpenApiSource(specPath) {
  accessSync(specPath, constants.R_OK)

  return readFileSync(specPath, 'utf8')
}

export function findContractProblems(openApiSource) {
  const requiredFragments = [
    ['OpenAPI 3.1 metadata', 'openapi: 3.1.0'],
    ['versioned API server', 'url: /api/v1'],
    ['health path', '  /health:'],
    ['health operation ID', 'operationId: getHealth'],
    ['health 200 response', "'200':"],
    ['health JSON content type', 'application/json:'],
    ['health response schema reference', "$ref: '#/components/schemas/HealthResponse'"],
    ['health response schema', '    HealthResponse:'],
    ['health status enum', '            - ok'],
    ['health API version metadata', '            - api_version'],
    ['health timestamp metadata', '            - timestamp'],
  ]

  const problems = requiredFragments
    .filter(([, fragment]) => !openApiSource.includes(fragment))
    .map(([label]) => `Missing ${label}.`)

  if (/\bexamples?:\b/i.test(openApiSource)) {
    problems.push('Generated contract source must not contain examples.')
  }

  return problems
}

export function buildGeneratedTypes(openApiSource) {
  const problems = findContractProblems(openApiSource)

  if (problems.length > 0) {
    throw new Error(problems.join('\n'))
  }

  return `// Generated from the Leverly OpenAPI contract. Do not edit manually.
// Run \`pnpm api-client:generate\` from apps/web to refresh.

export interface HealthResponse {
  readonly status: 'ok'
  readonly meta: {
    readonly api_version: 'v1'
    readonly timestamp: string
  }
}

export interface paths {
  readonly '/health': {
    readonly get: {
      readonly responses: {
        readonly 200: {
          readonly content: {
            readonly 'application/json': HealthResponse
          }
        }
      }
    }
  }
}
`
}

export function writeGeneratedTypes(openApiSource, outputPath) {
  const generatedTypes = buildGeneratedTypes(openApiSource)

  mkdirSync(path.dirname(outputPath), { recursive: true })
  writeFileSync(outputPath, generatedTypes, 'utf8')

  return generatedTypes
}

export function findGeneratedTypesProblems(openApiSource, generatedTypesPath) {
  const expected = buildGeneratedTypes(openApiSource)

  try {
    accessSync(generatedTypesPath, constants.R_OK)
  } catch {
    return [`Generated OpenAPI types are missing at ${generatedTypesPath}.`]
  }

  const actual = readFileSync(generatedTypesPath, 'utf8')

  if (actual !== expected) {
    return [`Generated OpenAPI types are stale at ${generatedTypesPath}.`]
  }

  return []
}
