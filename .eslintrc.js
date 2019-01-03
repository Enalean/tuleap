module.exports = {
    plugins: ["you-dont-need-lodash-underscore", "cypress", "vue"],
    extends: [
        "eslint:recommended",
        "plugin:you-dont-need-lodash-underscore/all",
        "plugin:vue/recommended"
    ],
    parser: "vue-eslint-parser",
    parserOptions: {
        parser: "babel-eslint",
        ecmaVersion: 2018,
        sourceType: "module"
    },
    env: {
        es6: true,
        browser: true
    },
    rules: {
        // Possible Errors
        "no-template-curly-in-string": "error",
        // Best Practices
        "array-callback-return": "warn",
        "consistent-return": "warn",
        curly: "error",
        "default-case": "warn",
        "dot-notation": "warn",
        eqeqeq: "warn",
        "no-alert": "error",
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
        "vue/attributes-order": "off",
        "vue/component-name-in-template-casing": ["error", "kebab-case"],
        "vue/html-self-closing": [
            "error",
            {
                html: {
                    normal: "any",
                    component: "always"
                },
                svg: "any"
            }
        ],
        "vue/html-closing-bracket-spacing": [
            "error",
            {
                selfClosingTag: "never"
            }
        ],
        "vue/html-indent": ["error", 4],
        "vue/max-attributes-per-line": "off",
        "vue/no-spaces-around-equal-signs-in-attribute": "error",
        "vue/prop-name-casing": "off",
        "vue/require-default-prop": "off",
        "vue/v-bind-style": ["error", "longform"],
        "vue/v-on-style": ["error", "longform"]
    },
    overrides: [
        {
            files: ["*.spec.js", "*.spec-helper.js"],
            env: {
                jasmine: true
            },
            globals: {
                // jasmine 3.2
                expectAsync: true,
                // jasmine-fixture
                affix: true,
                // jasmine-promise-matchers,
                installPromiseMatchers: true
            }
        },
        {
            files: [
                "gulpfile.js",
                "karma.conf.js",
                "plugins/tracker/grammar/",
                "tools/**/*.js",
                "webpack.config.js"
            ],
            env: {
                node: true
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
