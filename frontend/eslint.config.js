import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import globals from 'globals'

export default [
  {
    ignores: ['dist/**', 'node_modules/**', 'playwright-report/**', 'test-results/**'],
  },
  js.configs.recommended,
  ...pluginVue.configs['flat/essential'],
  {
    files: ['**/*.{js,mjs,cjs,vue}'],
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.node,
      },
    },
    rules: {
      'no-console': 'off',
      'no-debugger': 'warn',
      'no-empty': 'off',
      'no-unused-vars': 'off',
      'no-useless-assignment': 'off',
      'no-useless-escape': 'off',
      'vue/multi-word-component-names': 'off',
    },
  },
]
