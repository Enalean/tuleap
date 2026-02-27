/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import eslint_js from "@eslint/js"; // eslint-disable-line import/no-extraneous-dependencies -- It is indirectly imported by eslint
import { FlatCompat } from "@eslint/eslintrc"; // eslint-disable-line import/no-extraneous-dependencies -- It is indirectly imported by eslint
import { defineConfig, globalIgnores } from "eslint/config";
import globals from "globals";
import eslint_plugin_prettier_recommended from "eslint-plugin-prettier/recommended";
import eslint_config_prettier from "eslint-config-prettier";
import deprecate from "eslint-plugin-deprecate";
import plugin_cypress from "eslint-plugin-cypress/flat";
import plugin_vue from "eslint-plugin-vue";
import plugin_jest from "eslint-plugin-jest";
import typescript_eslint from "typescript-eslint";
import plugin_import from "eslint-plugin-import";
import plugin_no_unsanitized from "eslint-plugin-no-unsanitized";
import plugin_no_only_tests from "eslint-plugin-no-only-tests";
import storybook from "eslint-plugin-storybook";
import vue_eslint_parser from "vue-eslint-parser";
import { dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));

const node_files = [
    "stylelint.config.mjs",
    "eslint.config.mjs",
    "lib/frontend/potentially-dangerous-bidirectional-characters/src/potentially-dangerous-bidirectional-characters-cli.mjs",
    "plugins/tracker/grammar/**/*.js",
    "src/scripts/ckeditor4/copy_ckeditor.js",
    "tools/**/*.js",
    "lib/frontend/build-system-configurator/src/jest/*.js",
    "**/webpack.*.js",
    "**/webpack.*.mjs",
    "**/jest.config.js",
];

const config_for_browser_globals = {
    files: ["**/*.js", "**/*.ts", "**/*.vue"],
    ignores: node_files,
    languageOptions: { globals: { ...globals.browser } },
};

const compat = new FlatCompat({
    baseDirectory: __dirname,
});
const config_for_monorepo = compat.config({
    plugins: ["monorepo-cop"],
    extends: ["plugin:monorepo-cop/recommended"],
});

const config_for_import_and_sanitize_that_apply_everywhere = {
    plugins: {
        import: plugin_import,
        "no-unsanitized": plugin_no_unsanitized,
    },
    settings: {
        "import/core-modules": ["@jest/globals", "vitest"],
    },
    rules: {
        "import/no-duplicates": "error",
        "import/no-extraneous-dependencies": "error",
        "no-unsanitized/property": [
            "error",
            {
                escape: {
                    methods: ["sanitize", "DOMPurify.sanitize", "render"],
                },
            },
        ],
        "no-unsanitized/method": [
            "error",
            {
                escape: {
                    methods: [
                        "sanitize",
                        "DOMPurify.sanitize",
                        "render",
                        "getPOFileFromLocale",
                        "getPOFileFromLocaleWithoutExtension",
                        "mustache.render",
                    ],
                },
            },
        ],
    },
};

const config_for_rules_that_apply_everywhere = {
    rules: {
        // Possible Errors
        "no-template-curly-in-string": "error",
        // Best Practices
        "no-unused-vars": "off", // Replaced by @typescript-eslint/no-unused-vars
        "@typescript-eslint/no-unused-vars": ["error", { caughtErrorsIgnorePattern: "^_" }],
        "array-callback-return": "warn",
        "consistent-return": "warn",
        curly: "error",
        "default-case": "warn",
        "dot-notation": "warn",
        eqeqeq: "warn",
        "no-alert": "error",
        "no-console": "error",
        "no-caller": "error",
        "no-div-regex": "error",
        "no-else-return": "warn",
        "no-eval": "error",
        "no-extend-native": "error",
        "no-extra-bind": "error",
        "no-implicit-coercion": "error",
        "no-implied-eval": "error",
        "no-iterator": "error",
        "no-labels": "error",
        "no-lone-blocks": "error",
        "no-loop-func": "warn",
        "no-multi-str": "error",
        "no-new": "warn",
        "no-new-func": "warn",
        "no-new-wrappers": "error",
        "no-param-reassign": "warn",
        "no-proto": "error",
        "no-return-assign": "error",
        "no-return-await": "error",
        "no-self-compare": "error",
        "no-sequences": "error",
        "no-throw-literal": "error",
        "no-unmodified-loop-condition": "error",
        "no-useless-call": "error",
        "no-useless-concat": "error",
        "no-useless-return": "warn",
        "no-var": "off",
        "no-void": "error",
        "no-with": "error",
        radix: "error",
        "require-await": "error",
    },
};

const config_to_disable_rules_on_old_javascript_files = {
    files: ["**/*.js"],
    rules: { "@typescript-eslint/no-this-alias": "off" },
};

const config_for_typescript = {
    files: ["**/*.ts", "**/*.vue"],
    rules: {
        "no-restricted-syntax": [
            "error",
            {
                selector: "TSEnumDeclaration",
                message: `[Ban TypeScript Enum] Please replace by string constant union types ("before" | "after") or plain Javascript object (Direction.before, Direction.after).`,
            },
        ],
        "@typescript-eslint/camelcase": "off",
        "@typescript-eslint/class-literal-property-style": "error",
        "@typescript-eslint/consistent-type-assertions": ["error", { assertionStyle: "never" }],
        "@typescript-eslint/consistent-type-imports": "error",
        "@typescript-eslint/explicit-function-return-type": "error",
        "@typescript-eslint/naming-convention": [
            "error",
            { selector: "typeLike", format: ["PascalCase"] },
        ],
        "@typescript-eslint/no-explicit-any": "error",
        "@typescript-eslint/no-non-null-assertion": "error",
        "@typescript-eslint/no-use-before-define": [
            "error",
            { functions: false, typedefs: false, classes: false },
        ],
    },
};

const config_for_vue = {
    files: ["**/*.vue"],
    extends: [plugin_vue.configs["flat/recommended"]],
    rules: {
        // Priority B: Strongly Recommended (Improving Readability)
        "vue/html-self-closing": [
            "error",
            {
                html: { void: "any", normal: "any", component: "always" },
                svg: "any",
                math: "any",
            },
        ],
        "vue/prop-name-casing": ["error", "snake_case"],
        "vue/v-bind-style": ["error", "longform"],
        "vue/v-on-style": ["error", "longform"],
        "vue/v-slot-style": ["error", "longform"],
        // Priority C: Recommended
        "vue/attributes-order": "off",
        // Uncategorized
        "vue/component-name-in-template-casing": ["error", "kebab-case"],
        "vue/match-component-file-name": "error",
        "vue/require-direct-export": "off",
        // Conflicts with Prettier
        "vue/html-closing-bracket-newline": "off",
        "vue/html-indent": "off",
        "vue/max-attributes-per-line": "off",
        "vue/multiline-html-element-content-newline": "off",
        "vue/singleline-html-element-content-newline": "off",
    },
};

const config_for_remaining_vue_javascript = {
    // Disable some rules enabled by the Vue 3 recommended rules until remaining migrations compat issues can be resolved
    files: [
        "plugins/baseline/scripts/baseline/src/**/*.vue",
        "plugins/document/**/*.vue",
        "plugins/pullrequest/scripts/create-pullrequest-button/**/*.vue",
        "plugins/tracker/scripts/workflow-transitions/**/*.vue",
    ],
    rules: {
        "@typescript-eslint/explicit-function-return-type": "off",
        "vue/no-deprecated-destroyed-lifecycle": "off",
        "vue/no-deprecated-slot-attribute": "off",
        "vue/prop-name-casing": "off",
    },
};

const config_for_node_globals = {
    files: node_files,
    languageOptions: { globals: { ...globals.node } },
};

const config_for_node_cli_tools = {
    files: ["tools/**/*.js"],
    rules: { "no-console": "off" },
};

const config_for_remaining_commonjs = {
    files: [
        "**/jest.config.js",
        "**/webpack.*.js",
        "**/webpack.*.mjs",
        "tools/utils/scripts/**/*.js",
    ],
    rules: { "@typescript-eslint/no-require-imports": "off" },
};

const config_for_vite = {
    files: ["**/vite.config.ts"],
    rules: {
        "import/no-extraneous-dependencies": "off", // Vite and its plugins are installed globally
    },
};

const jest_rules = {
    "jest/consistent-test-it": "error",
    "jest/no-large-snapshots": ["error", { maxSize: 100 }],
    "jest/prefer-expect-resolves": "error",
    "jest/prefer-hooks-on-top": "error",
    "jest/prefer-spy-on": "error",
    "jest/prefer-strict-equal": "warn",
    "jest/require-top-level-describe": "error",
    "jest/valid-title": "error",
    // Style
    "jest/prefer-comparison-matcher": "error",
    "jest/prefer-equality-matcher": "error",
    "jest/prefer-to-be": "error",
    "jest/prefer-to-contain": "error",
    "jest/prefer-to-have-length": "error",
};

const config_for_javascript_tests = {
    files: [
        "**/*.test.js",
        "lib/frontend/build-system-configurator/src/jest/fail-console-error-warning.js",
    ],
    extends: [plugin_jest.configs["flat/recommended"]],
    rules: jest_rules,
};

const config_for_typescript_tests = {
    files: ["**/*.test.ts"],
    extends: [plugin_jest.configs["flat/recommended"]],
    rules: {
        ...jest_rules,
        "@typescript-eslint/consistent-type-assertions": ["error", { assertionStyle: "as" }],
        // Allow innerHTML in tests
        "no-unsanitized/property": "off",
        // Allow insertAdjacentHTML in tests
        "no-unsanitized/method": "off",
    },
};

const config_for_cypress_globals = {
    files: [
        "lib/test-utils/cypress-utilities-support/**/*.ts",
        "tests/e2e/full/cypress/**/*.ts",
        "plugins/**/tests/e2e/**/**/*.ts",
    ],
    extends: [plugin_cypress.configs.recommended],
};

const config_for_cypress = {
    files: ["tests/e2e/full/cypress/**/*.ts", "plugins/*/tests/e2e/**/*.ts"],
    plugins: {
        "no-only-tests": plugin_no_only_tests,
    },
    rules: {
        "cypress/require-data-selectors": "error",
        "cypress/no-unnecessary-waiting": "error",
        "cypress/no-assigning-return-values": "error",
        "cypress/assertion-before-screenshot": "error",
        "cypress/no-force": "warn",
        "no-only-tests/no-only-tests": "error",
    },
};

const config_to_temporarily_disable_cypress_unsafe_chain = {
    files: ["plugins/*/tests/e2e/**/*.ts"],
    rules: {
        "cypress/unsafe-to-chain-command": "off", // We should fix this in a dedicated issue
    },
};

const config_for_hexagonal_architecture_in_artifact_modal = {
    files: ["plugins/tracker/scripts/lib/artifact-modal/src/**/*.ts"],
    rules: {
        "import/no-restricted-paths": [
            "error",
            {
                basePath: "plugins/tracker/scripts/lib/artifact-modal/",
                zones: [
                    {
                        target: "src/domain/",
                        from: "src/",
                        except: ["domain"],
                        message: "Domain should not depend on the outside world",
                    },
                    {
                        target: "src/adapters/REST/",
                        from: "src/adapters/",
                        except: ["REST"],
                        message:
                            "Adapters should not depend on other adapters without going through the Domain",
                    },
                    {
                        target: "src/adapters/UI/",
                        from: "src/adapters/",
                        except: ["UI"],
                        message:
                            "Adapters should not depend on other adapters without going through the Domain",
                    },
                ],
            },
        ],
    },
};

const config_for_hexagonal_architecture_in_link_field = {
    files: ["plugins/tracker/scripts/lib/link-field/src/**/*.ts"],
    rules: {
        "import/no-restricted-paths": [
            "error",
            {
                basePath: "plugins/tracker/scripts/lib/link-field/",
                zones: [
                    {
                        target: "src/domain/",
                        from: "src/",
                        except: ["domain"],
                        message: "Domain should not depend on the outside world",
                    },
                    {
                        target: "src/adapters/REST/",
                        from: "src/adapters/",
                        except: ["REST"],
                        message:
                            "Adapters should not depend on other adapters without going through the Domain",
                    },
                    {
                        target: "src/adapters/UI/",
                        from: "src/adapters/",
                        except: ["UI"],
                        message:
                            "Adapters should not depend on other adapters without going through the Domain",
                    },
                    {
                        target: "src/adapters/Memory/",
                        from: "src/adapters/",
                        except: ["Memory"],
                        message:
                            "Adapters should not depend on other adapters without going through the Domain",
                    },
                ],
            },
        ],
    },
};

const config_for_deprecations = {
    plugins: { deprecate },
    rules: {
        "deprecate/function": ["error", { name: "initGettextSync", use: "initGettext" }],
    },
};

export default defineConfig([
    globalIgnores([
        "**/additional-packages/",
        "**/assets/",
        "**/backend-assets/",
        "**/bin/",
        "**/build/",
        "**/coverage/",
        "**/dist/",
        "**/frontend-assets/",
        "**/js-test-results/",
        "**/vendor/",
        "src/scripts/tlp-doc/storybook-static/",
        // Old legacy ignored files
        "plugins/mediawiki/www/**/*.js",
        "src/common/wiki/phpwiki/**/*.js",
        "src/www/scripts/bootstrap/**/*.js",
        "src/www/scripts/datepicker/**/*.js",
        "src/www/scripts/jquery/**/*.js",
        "src/www/scripts/prototype/**/*.js",
        "src/www/scripts/scriptaculous/**/*.js",
        "src/www/scripts/select2/**/*.js",
        "src/www/scripts/tablekit/**/*.js",
        "src/www/scripts/textboxlist/**/*.js",
        // select2 type files
        "src/scripts/tlp/src/types/**/*.d.ts",
    ]),
    { linterOptions: { reportUnusedDisableDirectives: "error" } },
    {
        files: ["**/*.vue"],
        languageOptions: {
            parser: vue_eslint_parser,
            parserOptions: { parser: typescript_eslint.parser },
        },
    },
    {
        files: ["**/*.ts"],
        languageOptions: { parser: typescript_eslint.parser },
    },
    eslint_js.configs.recommended,
    ...typescript_eslint.configs.recommended,
    eslint_plugin_prettier_recommended,
    eslint_config_prettier,
    plugin_import.flatConfigs.typescript,
    config_for_browser_globals,
    config_for_monorepo,
    config_for_import_and_sanitize_that_apply_everywhere,
    config_for_rules_that_apply_everywhere,
    config_to_disable_rules_on_old_javascript_files,
    config_for_typescript,
    config_for_vue,
    config_for_remaining_vue_javascript,
    config_for_node_globals,
    config_for_node_cli_tools,
    config_for_remaining_commonjs,
    config_for_vite,
    config_for_javascript_tests,
    config_for_typescript_tests,
    config_for_cypress_globals,
    config_for_cypress,
    config_to_temporarily_disable_cypress_unsafe_chain,
    ...storybook.configs["flat/recommended"],
    config_for_hexagonal_architecture_in_artifact_modal,
    config_for_hexagonal_architecture_in_link_field,
    config_for_deprecations,
]);
