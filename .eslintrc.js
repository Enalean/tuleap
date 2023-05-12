module.exports = {
    plugins: [
        "you-dont-need-lodash-underscore",
        "cypress",
        "vue",
        "jest",
        "@typescript-eslint",
        "import",
        "no-unsanitized",
        "no-only-tests",
        "monorepo-cop",
    ],
    extends: [
        "eslint:recommended",
        "plugin:you-dont-need-lodash-underscore/all",
        "plugin:vue/recommended",
        "plugin:import/typescript",
        "plugin:@typescript-eslint/recommended",
        "plugin:prettier/recommended",
        "plugin:monorepo-cop/recommended",
    ],
    parser: "vue-eslint-parser",
    parserOptions: {
        parser: "@typescript-eslint/parser",
        extraFileExtensions: [".vue"],
    },
    env: {
        es6: true,
        browser: true,
        "vue/setup-compiler-macros": true,
    },
    reportUnusedDisableDirectives: true,
    rules: {
        // Possible Errors
        "no-template-curly-in-string": "error",
        // Best Practices
        "no-unused-vars": "off",
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
        "no-void": "error",
        "no-with": "error",
        radix: "error",
        "require-await": "error",
        // Vue
        // Priority B: Strongly Recommended (Improving Readability)
        "vue/html-self-closing": [
            "error",
            {
                html: {
                    void: "any",
                    normal: "any",
                    component: "always",
                },
                svg: "any",
                math: "any",
            },
        ],
        "vue/v-bind-style": ["error", "longform"],
        "vue/v-on-style": ["error", "longform"],
        "vue/attributes-order": "off",
        "vue/prop-name-casing": "off", // This would be interesting, but the --fix does not rename all instances, which will silently break your code
        // Uncategorized
        "vue/component-name-in-template-casing": ["error", "kebab-case"],
        "vue/match-component-file-name": "error",
        "vue/require-direct-export": "off",
        // Typescript
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
        "@typescript-eslint/no-explicit-any": "error",
        "@typescript-eslint/no-non-null-assertion": "error",
        "@typescript-eslint/no-unused-vars": "error",
        "@typescript-eslint/no-use-before-define": [
            "error",
            { functions: false, typedefs: false, classes: false },
        ],
        // import
        "import/no-extraneous-dependencies": "error",
        "no-unsanitized/property": [
            "error",
            {
                escape: {
                    methods: ["sanitize", "render"],
                },
            },
        ],
        "no-unsanitized/method": [
            "error",
            {
                escape: {
                    methods: [
                        "sanitize",
                        "render",
                        "getPOFileFromLocale",
                        "getPOFileFromLocaleWithoutExtension",
                        "mustache.render",
                    ],
                },
            },
        ],
    },
    overrides: [
        {
            // Disable some rules enabled by @typescript-eslint/recommended for existing JS files
            files: ["*.js"],
            rules: {
                "@typescript-eslint/explicit-function-return-type": "off",
                "@typescript-eslint/explicit-module-boundary-types": "off",
                "@typescript-eslint/no-array-constructor": "off",
                "@typescript-eslint/no-empty-function": "off",
                "@typescript-eslint/no-this-alias": "off",
                "@typescript-eslint/no-use-before-define": "off",
                "@typescript-eslint/no-var-requires": "off",
                "no-var": "off",
                "prefer-const": "off",
                "prefer-rest-params": "off",
                "prefer-spread": "off",
            },
        },
        {
            // Disable some rules enabled by @typescript-eslint/recommended for existing Vue files
            files: [
                "plugins/document/**/*.vue",
                "plugins/tracker/**/*.vue",
                "plugins/timetracking/**/*.vue",
                "plugins/svn/**/*.vue",
                "plugins/pullrequest/**/*.vue",
                "plugins/label/**/*.vue",
                "plugins/git/**/*.vue",
                "plugins/testmanagement/**/*.vue",
                "plugins/baseline/**/*.vue",
                "plugins/create_test_env/**/*.vue",
                "plugins/crosstracker/**/*.vue",
                "plugins/agiledashboard/scripts/permissions-per-group/**/*.vue",
                "src/scripts/main/src/project/admin/services/**/*.vue",
                "src/scripts/main/src/*/permissions-per-group/**/*.vue",
            ],
            rules: {
                "vue/no-v-for-template-key": "off", //will be supported in vue3, we do not want template introduce div when we don't need them
                "@typescript-eslint/explicit-function-return-type": "off",
                "@typescript-eslint/explicit-module-boundary-types": "off",
                "prefer-const": "off",
                "no-var": "off",
            },
        },
        {
            files: [
                "*.test.js",
                "*.test.ts",
                "lib/frontend/build-system-configurator/src/jest/fail-console-error-warning.js",
            ],
            extends: ["plugin:jest/recommended"],
            rules: {
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
            },
        },
        {
            files: ["*.test.ts"],
            rules: {
                "@typescript-eslint/consistent-type-assertions": [
                    "error",
                    { assertionStyle: "as" },
                ],
                // Allow innerHTML in tests
                "no-unsanitized/property": "off",
                // Allow insertAdjacentHTML in tests
                "no-unsanitized/method": "off",
            },
        },
        {
            files: [
                ".eslintrc.js",
                "webpack.*.js",
                "plugins/tracker/grammar/",
                "tools/**/*.js",
                "jest.config.js",
                "jest.*.config.js",
                "lib/frontend/build-system-configurator/src/jest/*.js",
            ],
            env: {
                node: true,
            },
            rules: {
                "no-console": "off",
            },
        },
        {
            files: ["tests/e2e/**/*.ts", "plugins/**/tests/e2e/**/**/*.ts"],
            env: {
                "cypress/globals": true,
            },
            rules: {
                "cypress/require-data-selectors": "error",
                "cypress/no-unnecessary-waiting": "error",
                "cypress/no-assigning-return-values": "error",
                "cypress/assertion-before-screenshot": "error",
                "cypress/no-force": "warn",
                "no-only-tests/no-only-tests": "error",
            },
        },
        {
            files: ["vite.config.ts"],
            rules: {
                "import/no-extraneous-dependencies": "off", // Vite is installed globally
            },
        },
        // Specify Vue 3 app and library
        // At some point in the future this should the default and the Vue 2 rules should be explicitly enabled on the
        // remaining apps
        {
            files: [
                "lib/frontend/project-sidebar-internal/**/*.vue",
                "plugins/document_generation/scripts/**/*.vue",
                "plugins/testplan/scripts/**/*.vue",
                "plugins/gitlab/scripts/**/*.vue",
                "plugins/git/scripts/artifact-create-branch-action/**/*.vue",
                "plugins/onlyoffice/scripts/siteadmin/**/*.vue",
                "plugins/roadmap/scripts/configure-widget/**/*.vue",
            ],
            extends: ["plugin:vue/vue3-recommended"],
            rules: {
                "vue/html-indent": "off", // Conflict with Prettier
                "vue/max-attributes-per-line": "off", // Conflict with Prettier
                "vue/no-multiple-template-root": "off", // Specific to Vue 2
                "vue/no-v-model-argument": "off", // Specific to Vue 2
                // Priority B: Strongly Recommended (Improving Readability)
                "vue/html-self-closing": [
                    "error",
                    {
                        html: {
                            void: "any",
                            normal: "any",
                            component: "always",
                        },
                        svg: "any",
                        math: "any",
                    },
                ],
                "vue/v-bind-style": ["error", "longform"],
                "vue/v-on-style": ["error", "longform"],
                "vue/attributes-order": "off",
                "vue/prop-name-casing": ["error", "snake_case"],
                // Uncategorized
                "vue/component-name-in-template-casing": ["error", "kebab-case"],
                "vue/match-component-file-name": "error",
                // Typescript
                "@typescript-eslint/explicit-function-return-type": "error",
            },
        },
        {
            // Enforce Hexagonal Architecture
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
                                    "Adapters should not depend on other adapter without going through the Domain",
                            },
                            {
                                target: "src/adapters/UI/",
                                from: "src/adapters/",
                                except: ["UI"],
                                message:
                                    "Adapters should not depend on other adapter without going through the Domain",
                            },
                            {
                                target: "src/adapters/Memory/",
                                from: "src/adapters/",
                                except: ["Memory"],
                                message:
                                    "Adapters should not depend on other adapter without going through the Domain",
                            },
                            {
                                target: "src/adapters/Caller/",
                                from: "src/adapters/",
                                except: ["Caller"],
                                message:
                                    "Adapters should not depend on other adapter without going through the Domain",
                            },
                        ],
                    },
                ],
            },
        },
    ],
    settings: {
        "import/core-modules": ["vitest", "vite-plugin-dts"],
    },
};
