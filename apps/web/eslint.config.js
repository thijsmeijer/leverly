import js from '@eslint/js'
import eslintConfigPrettier from '@vue/eslint-config-prettier'
import pluginVue from 'eslint-plugin-vue'
import tseslint from 'typescript-eslint'
import architecture from './eslint-rules/architecture.js'

export default [
  {
    ignores: ['dist/**', 'node_modules/**', 'coverage/**', 'playwright-report/**', 'test-results/**'],
  },
  js.configs.recommended,
  {
    languageOptions: {
      globals: {
        document: 'readonly',
        getComputedStyle: 'readonly',
        navigator: 'readonly',
        window: 'readonly',
      },
    },
  },
  ...tseslint.configs.recommended,
  ...pluginVue.configs['flat/recommended'],
  eslintConfigPrettier,
  {
    files: [
      '*.config.js',
      '*.config.ts',
      'eslint-rules/**/*.js',
      'eslint-rules/**/*.mjs',
      'scripts/**/*.js',
      'scripts/**/*.mjs',
    ],
    languageOptions: {
      globals: {
        console: 'readonly',
        process: 'readonly',
      },
    },
  },
  {
    plugins: {
      'leverly-architecture': architecture,
    },
    rules: {
      'leverly-architecture/no-generated-openapi-internals': 'error',
      'leverly-architecture/no-shared-upstream-imports': 'error',
      'leverly-architecture/no-feature-app-imports': 'error',
      'leverly-architecture/no-cross-feature-deep-imports': 'error',
      'leverly-architecture/no-app-module-deep-imports': 'error',
    },
  },
  {
    files: ['**/*.vue'],
    languageOptions: {
      parserOptions: {
        parser: tseslint.parser,
      },
    },
    rules: {
      'vue/multi-word-component-names': 'off',
    },
  },
]
