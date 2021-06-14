const fs = require('fs')

const prettier_config = JSON.parse(fs.readFileSync("./.prettierrc", "utf-8"));

module.exports = {
    extends: ["stylelint-config-sass-guidelines", "stylelint-config-property-sort-order-smacss", "stylelint-prettier/recommended"],
    syntax: "scss",
    reportNeedlessDisables: true,
    reportInvalidScopeDisables: true,
    rules: {
        indentation: [4],
        "comment-word-disallowed-list": [
            [/^!/],
            {
                message: `Never use the "/*!" style of comments. Those comments are output in compressed CSS. (comment-word-disallowed-list)`
            }
        ],
        "color-hex-length": ["long"],
        "color-named": [
            "never",
            {
                message: "Colors should be written in hexadecimal format (color-named)"
            }
        ],
        "declaration-block-no-duplicate-properties": true,
        "function-calc-no-unspaced-operator": true,
        "max-nesting-depth": [4],
        "no-duplicate-selectors": true,
        "no-eol-whitespace": true,
        "number-leading-zero": ["always"],
        "selector-id-pattern": [
            "^[a-z0-9\\-]+$",
            {
                message:
                    "Selector should be written in lowercase with hyphens (selector-id-pattern)"
            }
        ],
        "selector-max-compound-selectors": [5],
        "selector-max-id": null,
        "selector-no-qualifying-type": [
            true,
            {
                ignore: ["attribute"]
            }
        ],
        "unit-allowed-list": [
            "em",
            "rem",
            "px",
            "vh",
            "vw",
            "vmin",
            "vmax",
            "deg",
            "grad",
            "rad",
            "turn",
            "ms",
            "s",
            "%",
            "fr"
        ],
        // Plugins
        "order/properties-alphabetical-order": null,
        "scss/at-else-empty-line-before": ["never"],
        "scss/dollar-variable-colon-space-before": null,
        "scss/operator-no-unspaced": true,
        "prettier/prettier": [
            true,
            {...prettier_config, "printWidth": 9999} // Override printWidth to play nice with existing .scss files
        ]
    }
};
