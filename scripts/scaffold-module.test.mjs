import assert from 'node:assert/strict'
import { mkdtempSync, readFileSync, rmSync, statSync } from 'node:fs'
import { tmpdir } from 'node:os'
import path from 'node:path'
import { execFileSync, spawnSync } from 'node:child_process'
import test from 'node:test'

const scriptPath = path.resolve('scripts/scaffold-module.mjs')

function tempRoot() {
  return mkdtempSync(path.join(tmpdir(), 'leverly-scaffold-'))
}

function cleanup(root) {
  rmSync(root, { recursive: true, force: true })
}

function run(args, root) {
  return execFileSync('node', [scriptPath, ...args, '--root', root], {
    cwd: path.resolve('.'),
    encoding: 'utf8',
  })
}

function fail(args, root) {
  return spawnSync('node', [scriptPath, ...args, '--root', root], {
    cwd: path.resolve('.'),
    encoding: 'utf8',
  })
}

function exists(root, target) {
  return statSync(path.join(root, target), { throwIfNoEntry: false }) !== undefined
}

test('web scaffold dry-run prints intended module files without writing them', () => {
  const root = tempRoot()

  try {
    const output = run(['web', '--module', 'workout-logger', '--dry-run'], root)

    assert.match(output, /apps\/web\/src\/modules\/workoutLogger\/pages\/WorkoutLoggerPage.vue/)
    assert.match(output, /apps\/web\/src\/modules\/workoutLogger\/routes.ts/)
    assert.equal(exists(root, 'apps/web/src/modules/workoutLogger'), false)
  } finally {
    cleanup(root)
  }
})

test('web scaffold creates the standard module structure', () => {
  const root = tempRoot()

  try {
    run(['web', '--module', 'workout-logger'], root)

    for (const target of [
      'apps/web/src/modules/workoutLogger/pages/WorkoutLoggerPage.vue',
      'apps/web/src/modules/workoutLogger/components/.gitkeep',
      'apps/web/src/modules/workoutLogger/composables/useWorkoutLogger.ts',
      'apps/web/src/modules/workoutLogger/services/workoutLoggerService.ts',
      'apps/web/src/modules/workoutLogger/routes.ts',
      'apps/web/src/modules/workoutLogger/types.ts',
      'apps/web/src/modules/workoutLogger/index.ts',
    ]) {
      assert.equal(exists(root, target), true, target)
    }

    const types = readFileSync(path.join(root, 'apps/web/src/modules/workoutLogger/types.ts'), 'utf8')
    assert.match(types, /Workout Logger/)
  } finally {
    cleanup(root)
  }
})

test('api scaffold creates a generic domain and route placeholder', () => {
  const root = tempRoot()

  try {
    run(['api', '--module', 'training_sessions'], root)

    for (const target of [
      'apps/api/app/Domain/TrainingSessions/Actions/.gitkeep',
      'apps/api/app/Domain/TrainingSessions/Data/.gitkeep',
      'apps/api/app/Domain/TrainingSessions/Queries/.gitkeep',
      'apps/api/app/Domain/TrainingSessions/Services/.gitkeep',
      'apps/api/routes/api/v1/training-sessions.php',
    ]) {
      assert.equal(exists(root, target), true, target)
    }

    const route = readFileSync(path.join(root, 'apps/api/routes/api/v1/training-sessions.php'), 'utf8')
    assert.match(route, /training-sessions/)
  } finally {
    cleanup(root)
  }
})

test('scaffold commands fail safely for invalid names and existing targets', () => {
  const root = tempRoot()

  try {
    const invalid = fail(['web', '--module', '../bad'], root)
    assert.notEqual(invalid.status, 0)
    assert.match(invalid.stderr, /Invalid module name/)

    run(['api', '--module', 'training'], root)
    const duplicate = fail(['api', '--module', 'training'], root)
    assert.notEqual(duplicate.status, 0)
    assert.match(duplicate.stderr, /already exists/)
  } finally {
    cleanup(root)
  }
})

test('web module structure check accepts compliant modules and rejects missing files', () => {
  const root = tempRoot()

  try {
    run(['web', '--module', 'workouts'], root)
    const ok = run(['check-web'], root)
    assert.match(ok, /Web module structure is valid/)

    rmSync(path.join(root, 'apps/web/src/modules/workouts/routes.ts'))
    const broken = fail(['check-web'], root)
    assert.notEqual(broken.status, 0)
    assert.match(broken.stderr, /routes\.ts/)
  } finally {
    cleanup(root)
  }
})
