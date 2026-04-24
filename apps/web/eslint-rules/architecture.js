import path from 'node:path'

const clientInternalPattern = /(^|[/@])api\/generated(\/|$)/

function normalizePath(value) {
  return value.split(path.sep).join('/')
}

function normalizedFilename(context) {
  return normalizePath(context.filename ?? context.getFilename())
}

function sourceValue(node) {
  return typeof node.source.value === 'string' ? node.source.value : ''
}

function isRelativeSource(source) {
  return source.startsWith('.')
}

function resolveSource(filename, source) {
  if (source.startsWith('@/')) {
    return `/src/${source.slice(2)}`
  }

  if (isRelativeSource(source)) {
    return normalizePath(path.resolve(path.dirname(filename), source))
  }

  return source
}

function isInsideSrc(filename, directory) {
  return filename.includes(`/src/${directory}/`)
}

function layerForPath(resolvedSource) {
  const normalized = normalizePath(resolvedSource)
  const srcIndex = normalized.indexOf('/src/')
  const srcPath = srcIndex >= 0 ? normalized.slice(srcIndex + '/src/'.length) : normalized.replace(/^\/src\//, '')
  return srcPath.split('/')[0] ?? ''
}

function areaName(resolvedSource, area) {
  const normalized = normalizePath(resolvedSource)
  const marker = `/src/${area}/`
  const markerIndex = normalized.indexOf(marker)

  if (markerIndex >= 0) {
    return normalized.slice(markerIndex + marker.length).split('/')[0] ?? ''
  }

  if (normalized.startsWith(`/src/${area}/`)) {
    return normalized.slice(`/src/${area}/`.length).split('/')[0] ?? ''
  }

  return ''
}

function isPublicAreaImport(resolvedSource, area, name) {
  const normalized = normalizePath(resolvedSource).replace(/\.(ts|tsx|vue|js|mjs|cjs)$/, '')
  const publicRoot = `/src/${area}/${name}`

  return normalized === publicRoot || normalized === `${publicRoot}/index`
}

function createImportVisitor(checkImport) {
  return {
    ImportDeclaration(node) {
      checkImport(node)
    },
    ExportNamedDeclaration(node) {
      if (node.source) {
        checkImport(node)
      }
    },
    ExportAllDeclaration(node) {
      checkImport(node)
    },
  }
}

function report(context, node, message) {
  context.report({
    node: node.source,
    message,
  })
}

const noGeneratedOpenApiInternals = {
  meta: {
    type: 'problem',
    docs: {
      description: 'Prevent app, feature, and module code from importing generated OpenAPI client internals.',
    },
    schema: [],
  },
  create(context) {
    const filename = normalizedFilename(context)
    const checkedArea =
      isInsideSrc(filename, 'app/pages') || isInsideSrc(filename, 'features') || isInsideSrc(filename, 'modules')

    if (!checkedArea) {
      return {}
    }

    return createImportVisitor((node) => {
      const source = sourceValue(node)

      if (clientInternalPattern.test(source)) {
        report(context, node, 'Use typed API wrapper modules instead of generated OpenAPI client internals.')
      }
    })
  },
}

const noSharedUpstreamImports = {
  meta: {
    type: 'problem',
    docs: {
      description: 'Prevent shared code from importing app, feature, or module code.',
    },
    schema: [],
  },
  create(context) {
    const filename = normalizedFilename(context)

    if (!isInsideSrc(filename, 'shared')) {
      return {}
    }

    return createImportVisitor((node) => {
      const resolved = resolveSource(filename, sourceValue(node))
      const layer = layerForPath(resolved)

      if (['app', 'features', 'modules'].includes(layer)) {
        report(context, node, 'Shared code must not import app, feature, or module code.')
      }
    })
  },
}

const noFeatureAppImports = {
  meta: {
    type: 'problem',
    docs: {
      description: 'Prevent feature and module code from importing app internals.',
    },
    schema: [],
  },
  create(context) {
    const filename = normalizedFilename(context)

    if (!isInsideSrc(filename, 'features') && !isInsideSrc(filename, 'modules')) {
      return {}
    }

    return createImportVisitor((node) => {
      const resolved = resolveSource(filename, sourceValue(node))

      if (layerForPath(resolved) === 'app') {
        report(context, node, 'Feature and module code must not import app internals.')
      }
    })
  },
}

const noCrossFeatureDeepImports = {
  meta: {
    type: 'problem',
    docs: {
      description: 'Prevent cross-feature and cross-module deep imports.',
    },
    schema: [],
  },
  create(context) {
    const filename = normalizedFilename(context)
    const owningArea = isInsideSrc(filename, 'features')
      ? 'features'
      : isInsideSrc(filename, 'modules')
        ? 'modules'
        : ''

    if (!owningArea) {
      return {}
    }

    const owner = areaName(filename, owningArea)

    return createImportVisitor((node) => {
      const resolved = resolveSource(filename, sourceValue(node))
      const targetLayer = layerForPath(resolved)

      if (targetLayer !== owningArea) {
        return
      }

      const target = areaName(resolved, owningArea)

      if (!target || target === owner || isPublicAreaImport(resolved, owningArea, target)) {
        return
      }

      report(context, node, `Import ${owningArea} code through its public index instead of deep imports.`)
    })
  },
}

export default {
  rules: {
    'no-generated-openapi-internals': noGeneratedOpenApiInternals,
    'no-shared-upstream-imports': noSharedUpstreamImports,
    'no-feature-app-imports': noFeatureAppImports,
    'no-cross-feature-deep-imports': noCrossFeatureDeepImports,
  },
}
