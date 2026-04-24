import {
  readOpenApiSource,
  resolveGeneratedTypesPath,
  resolveSpecPath,
  writeGeneratedTypes,
} from './openapi-client.mjs'

const specPath = resolveSpecPath()
const outputPath = resolveGeneratedTypesPath()
const openApiSource = readOpenApiSource(specPath)

writeGeneratedTypes(openApiSource, outputPath)

console.log(`OpenAPI types generated from ${specPath}`)
console.log(`OpenAPI types written to ${outputPath}`)
