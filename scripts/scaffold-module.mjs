#!/usr/bin/env node

import {
  existsSync,
  mkdirSync,
  readdirSync,
  statSync,
  writeFileSync,
} from 'node:fs'
import path from 'node:path'

const webRequiredDirectories = ['pages', 'components', 'composables', 'services']
const webRequiredFiles = ['routes.ts', 'types.ts', 'index.ts']
const apiDomainDirectories = ['Actions', 'Data', 'Queries', 'Services']

main()

function main() {
  try {
    const { command, dryRun, moduleName, root } = parseArgs(process.argv.slice(2))

    if (command === 'api') {
      scaffoldApiModule(root, moduleName, dryRun)
      return
    }

    if (command === 'web') {
      scaffoldWebModule(root, moduleName, dryRun)
      return
    }

    if (command === 'check-web') {
      checkWebModules(root)
      return
    }

    fail(`Unknown command: ${command}`)
  } catch (error) {
    fail(error instanceof Error ? error.message : String(error))
  }
}

function parseArgs(args) {
  const command = args[0]
  let dryRun = false
  let moduleName = process.env.MODULE ?? ''
  let root = process.cwd()

  for (let index = 1; index < args.length; index += 1) {
    const value = args[index]

    if (value === '--dry-run') {
      dryRun = true
      continue
    }

    if (value === '--module') {
      moduleName = args[index + 1] ?? ''
      index += 1
      continue
    }

    if (value === '--root') {
      root = args[index + 1] ?? ''
      index += 1
      continue
    }

    fail(`Unknown option: ${value}`)
  }

  return {
    command,
    dryRun,
    moduleName,
    root: path.resolve(root),
  }
}

function scaffoldApiModule(root, moduleName, dryRun) {
  const name = normalizeModuleName(moduleName)
  const domainName = name.pascal
  const routeFile = path.join(root, 'apps/api/routes/api/v1', `${name.kebab}.php`)
  const domainRoot = path.join(root, 'apps/api/app/Domain', domainName)

  assertTargetAvailable(domainRoot)
  assertTargetAvailable(routeFile)

  const outputs = [
    ...apiDomainDirectories.map((directory) => path.join(domainRoot, directory, '.gitkeep')),
    routeFile,
  ]

  if (dryRun) {
    printDryRun(outputs, root)
    return
  }

  for (const directory of apiDomainDirectories) {
    writeFile(path.join(domainRoot, directory, '.gitkeep'), '')
  }

  writeFile(routeFile, apiRouteTemplate(name.kebab))
  printCreated(outputs, root)
}

function scaffoldWebModule(root, moduleName, dryRun) {
  const name = normalizeModuleName(moduleName)
  const moduleRoot = path.join(root, 'apps/web/src/modules', name.camel)

  assertTargetAvailable(moduleRoot)

  const outputs = [
    path.join(moduleRoot, 'pages', `${name.pascal}Page.vue`),
    path.join(moduleRoot, 'components', '.gitkeep'),
    path.join(moduleRoot, 'composables', `use${name.pascal}.ts`),
    path.join(moduleRoot, 'services', `${name.camel}Service.ts`),
    path.join(moduleRoot, 'routes.ts'),
    path.join(moduleRoot, 'types.ts'),
    path.join(moduleRoot, 'index.ts'),
  ]

  if (dryRun) {
    printDryRun(outputs, root)
    return
  }

  writeFile(path.join(moduleRoot, 'pages', `${name.pascal}Page.vue`), webPageTemplate(name))
  writeFile(path.join(moduleRoot, 'components', '.gitkeep'), '')
  writeFile(path.join(moduleRoot, 'composables', `use${name.pascal}.ts`), webComposableTemplate(name))
  writeFile(path.join(moduleRoot, 'services', `${name.camel}Service.ts`), webServiceTemplate(name))
  writeFile(path.join(moduleRoot, 'routes.ts'), webRoutesTemplate(name))
  writeFile(path.join(moduleRoot, 'types.ts'), webTypesTemplate(name))
  writeFile(path.join(moduleRoot, 'index.ts'), webIndexTemplate(name))
  printCreated(outputs, root)
}

function checkWebModules(root) {
  const modulesRoot = path.join(root, 'apps/web/src/modules')

  if (!existsSync(modulesRoot)) {
    console.log('No web modules exist yet.')
    return
  }

  const failures = []

  for (const moduleName of readdirSync(modulesRoot).sort()) {
    const moduleRoot = path.join(modulesRoot, moduleName)

    if (!statSync(moduleRoot).isDirectory()) {
      continue
    }

    for (const directory of webRequiredDirectories) {
      const target = path.join(moduleRoot, directory)

      if (!existsSync(target) || !statSync(target).isDirectory()) {
        failures.push(relative(root, target))
      }
    }

    for (const file of webRequiredFiles) {
      const target = path.join(moduleRoot, file)

      if (!existsSync(target) || !statSync(target).isFile()) {
        failures.push(relative(root, target))
      }
    }
  }

  if (failures.length > 0) {
    fail(`Web module structure is incomplete:\n${failures.map((item) => `- ${item}`).join('\n')}`)
  }

  console.log('Web module structure is valid.')
}

function normalizeModuleName(value) {
  if (!value) {
    throw new Error('MODULE is required.')
  }

  if (!/^[a-z][a-z0-9]*(?:[-_][a-z0-9]+)*$/.test(value)) {
    throw new Error('Invalid module name. Use lowercase words separated by hyphen or underscore.')
  }

  const words = value.split(/[-_]/)
  const pascal = words.map(capitalize).join('')
  const camel = `${words[0]}${words.slice(1).map(capitalize).join('')}`
  const kebab = words.join('-')
  const title = words.map(capitalize).join(' ')

  return { camel, kebab, pascal, title }
}

function capitalize(value) {
  return `${value.charAt(0).toUpperCase()}${value.slice(1)}`
}

function assertTargetAvailable(target) {
  if (existsSync(target)) {
    throw new Error(`Target already exists: ${target}`)
  }
}

function writeFile(target, contents) {
  mkdirSync(path.dirname(target), { recursive: true })
  writeFileSync(target, contents)
}

function printDryRun(outputs, root) {
  console.log('Dry run. Files and directories that would be created:')
  for (const output of outputs) {
    console.log(`- ${relative(root, output)}`)
  }
}

function printCreated(outputs, root) {
  console.log('Created:')
  for (const output of outputs) {
    console.log(`- ${relative(root, output)}`)
  }
}

function relative(root, target) {
  return path.relative(root, target).split(path.sep).join('/')
}

function fail(message) {
  console.error(message)
  process.exit(1)
}

function apiRouteTemplate(prefix) {
  return `<?php

use Illuminate\\Support\\Facades\\Route;

Route::prefix('${prefix}')->name('${prefix}.')->group(function (): void {
});
`
}

function webPageTemplate(name) {
  return `<script setup lang="ts">
import { ${name.camel}Title } from '../types'
</script>

<template>
  <main class="bg-lab-void min-h-screen px-5 py-6 text-stone-100 sm:px-8">
    <section class="mx-auto flex min-h-screen w-full max-w-5xl items-center">
      <div class="w-full rounded-2xl border border-white/10 bg-white/8 p-6 shadow-lab-shell backdrop-blur">
        <p class="text-sm font-semibold text-emerald-200">Leverly</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-normal sm:text-5xl">{{ ${name.camel}Title }}</h1>
        <p class="mt-4 max-w-2xl text-base leading-7 text-stone-300">
          Keep progression evidence close before changing leverage.
        </p>
      </div>
    </section>
  </main>
</template>
`
}

function webComposableTemplate(name) {
  return `import { computed } from 'vue'

import { ${name.camel}Title } from '../types'

export function use${name.pascal}() {
  return {
    title: computed(() => ${name.camel}Title),
  }
}
`
}

function webServiceTemplate(name) {
  return `import type { ${name.pascal}Overview } from '../types'

export async function fetch${name.pascal}Overview(): Promise<${name.pascal}Overview> {
  return {
    title: '${name.title}',
  }
}
`
}

function webRoutesTemplate(name) {
  return `import type { RouteRecordRaw } from 'vue-router'

import ${name.pascal}Page from './pages/${name.pascal}Page.vue'

export const ${name.camel}Routes: RouteRecordRaw[] = [
  {
    path: '/app/${name.kebab}',
    name: '${name.camel}',
    component: ${name.pascal}Page,
    meta: {
      requiresAuth: true,
      title: '${name.title}',
    },
  },
]
`
}

function webTypesTemplate(name) {
  return `export const ${name.camel}Title = '${name.title}'

export interface ${name.pascal}Overview {
  readonly title: string
}
`
}

function webIndexTemplate(name) {
  return `export { default as ${name.pascal}Page } from './pages/${name.pascal}Page.vue'
export { ${name.camel}Routes } from './routes'
`
}
