import globals from "globals";
import pluginJs from "@eslint/js";
import jqueryPlugin from "eslint-plugin-jquery";

export default [
  {
    ignores: [
      "node_modules/**",
      "vendor/**",
      "tests/**",
      "docs/**",
      "resources/vendor/**"
    ]
  },
  {
    files: ["resources/**/*.js"],
    languageOptions: {
      ecmaVersion: 2020,
      sourceType: "script",
      globals: {
        ...globals.browser,
        PhpDebugBar: "writable",
        jQuery: "readonly",
        $: "readonly",
        hljs: "readonly"
      }
    },
    plugins: {
      jquery: jqueryPlugin
    },
    rules: {
      ...pluginJs.configs.recommended.rules,

      // ES6+ rules
      "prefer-const": "error",
      "no-var": "error",
      "prefer-arrow-callback": ["warn", { allowNamedFunctions: true }],
      "prefer-template": "warn",
      "object-shorthand": ["warn", "always"],
      "prefer-destructuring": ["warn", { object: true, array: false }],

      // Code quality
      "no-unused-vars": ["error", { args: "none", varsIgnorePattern: "^_" }],
      "no-console": "off", // Debug library, console is expected
      "eqeqeq": ["error", "always"],
      "curly": ["error", "all"],
      "brace-style": ["error", "1tbs"],
      "comma-dangle": ["error", "never"],
      "quotes": ["error", "single", { avoidEscape: true }],
      "semi": ["error", "always"],
      "indent": ["error", 4, { SwitchCase: 1 }],

      // jQuery-specific
      "jquery/no-ajax": "off",
      "jquery/no-each": "off", // We migrated most but some remain
      "jquery/no-extend": "warn",
      "jquery/no-val": "off",

      // Allow function expressions (for Widget.extend pattern)
      "func-style": "off",

      // Relax some rules for legacy patterns
      "no-prototype-builtins": "off",
      "no-useless-escape": "warn"
    }
  }
];
