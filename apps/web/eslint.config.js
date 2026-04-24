import js from '@eslint/js'
import eslintConfigPrettier from '@vue/eslint-config-prettier'
import pluginVue from 'eslint-plugin-vue'
import tseslint from 'typescript-eslint'

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
