module.exports = {
    plugins: [
        "you-dont-need-lodash-underscore",
        "cypress",
        "vue",
        "jest",
        "@typescript-eslint",
        "import",
        "no-unsanitized"
    ],
    extends: [
        "eslint:recommended",
        "plugin:you-dont-need-lodash-underscore/all",
        "plugin:vue/recommended", // We do not use "recommended" because it conflicts with Prettier formatting
        "plugin:@typescript-eslint/eslint-recommended",
        "plugin:import/typescript",
        "plugin:@typescript-eslint/recommended",
        "prettier",
        "prettier/@typescript-eslint",
        "prettier/vue",
        "plugin:prettier/recommended"
    ],
    parser: "vue-eslint-parser",
    parserOptions: {
        parser: "@typescript-eslint/parser",
        extraFileExtensions: [".vue"]
    },
    env: {
        es6: true,
        browser: true
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
                    component: "always"
                },
                svg: "any",
                math: "any"
            }
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
        "@typescript-eslint/camelcase": "off",
        "@typescript-eslint/class-literal-property-style": "error",
        "@typescript-eslint/consistent-type-assertions": ["error", { assertionStyle: "never" }],
        "@typescript-eslint/explicit-function-return-type": "error",
        "@typescript-eslint/no-explicit-any": "error",
        "@typescript-eslint/no-non-null-assertion": "error",
        "@typescript-eslint/no-unused-vars": "error",
        "@typescript-eslint/no-use-before-define": ["error", { functions: false, typedefs: false }],
        // import
        "import/no-extraneous-dependencies": "error",
        "no-unsanitized/property": [
            "error",
            {
                escape: {
                    methods: ["sanitize", "render"]
                }
            }
        ],
        "no-unsanitized/method": [
            "error",
            {
                escape: {
                    methods: ["sanitize", "render", "getPOFileFromLocale"]
                }
            }
        ]
    },
    overrides: [
        {
            // Disable some rules enabled by @typescript-eslint/recommended for existing JS files
            files: ["*.js"],
            rules: {
                "@typescript-eslint/no-var-requires": "off",
                "@typescript-eslint/explicit-function-return-type": "off",
                "prefer-const": "off",
                "no-var": "off",
                "prefer-rest-params": "off",
                "prefer-spread": "off",
                "@typescript-eslint/no-array-constructor": "off",
                "@typescript-eslint/no-use-before-define": "off",
                "@typescript-eslint/no-this-alias": "off",
                "@typescript-eslint/no-empty-function": "off"
            }
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
                "plugins/agiledashboard/www/js/permissions-per-group/**/*.vue",
                "src/www/scripts/project/admin/services/**/*.vue",
                "src/www/scripts/*/permissions-per-group/**/*.vue",
                "src/www/scripts/vue-components/skeletons/SkeletonTable.vue"
            ],
            rules: {
                "@typescript-eslint/explicit-function-return-type": "off",
                "prefer-const": "off",
                "no-var": "off"
            }
        },
        {
            files: ["*.test.js", "*.test.ts", "tests/jest/fail-console-error-warning.js"],
            extends: ["plugin:jest/recommended"],
            rules: {
                "jest/consistent-test-it": "error",
                "jest/valid-title": "error",
                "jest/no-expect-resolves": "error",
                "jest/no-large-snapshots": ["error", { maxSize: 100 }],
                "jest/prefer-spy-on": "error",
                "jest/require-top-level-describe": "error",
                "jest/prefer-hooks-on-top": "error"
            }
        },
        {
            files: ["*.test.ts"],
            rules: {
                "@typescript-eslint/consistent-type-assertions": ["error", { assertionStyle: "as" }]
            }
        },
        {
            files: [
                "gulpfile.js",
                "webpack.*.js",
                "plugins/tracker/grammar/",
                "tools/**/*.js",
                "jest.config.js",
                "jest.projects.config.js",
                "tests/jest/*.js"
            ],
            env: {
                node: true
            },
            rules: {
                "no-console": "off"
            }
        },
        {
            files: ["tests/e2e/**/*.js"],
            env: {
                "cypress/globals": true
            }
        }
    ]
};
