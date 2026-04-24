import {
  findContractProblems,
  findGeneratedTypesProblems,
  readOpenApiSource,
  resolveGeneratedTypesPath,
  resolveSpecPath,
} from './openapi-client.mjs'

const specPath = resolveSpecPath()
const generatedTypesPath = resolveGeneratedTypesPath()

try {
  const openApiSource = readOpenApiSource(specPath)
  const problems = [
    ...findContractProblems(openApiSource),
    ...findGeneratedTypesProblems(openApiSource, generatedTypesPath),
  ]

  if (problems.length > 0) {
    throw new Error(problems.join('\n'))
  }

  console.log(`OpenAPI source is current: ${specPath}`)
  console.log(`OpenAPI types are current: ${generatedTypesPath}`)
} catch (error) {
  const detail = error instanceof Error ? error.message : String(error)

  console.error(`OpenAPI client check failed for source: ${specPath}`)
  console.error(detail)
  process.exit(1)
}
