module.exports = {
    extends: ["stylelint-config-sass-guidelines", "stylelint-config-property-sort-order-smacss"],
    syntax: "scss",
    reportNeedlessDisables: true,
    reportInvalidScopeDisables: true,
    rules: {
        indentation: [4],
        "comment-word-blacklist": [
            [/^!/],
            {
                message: `Never use the "/*!" style of comments. Those comments are output in compressed CSS. (comment-word-blacklist)`
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
        "number-leading-zero": ["never"],
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
        "unit-whitelist": [
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
            "%"
        ],
        // Plugins
        "order/properties-alphabetical-order": null,
        "scss/at-else-empty-line-before": ["never"],
        "scss/dollar-variable-colon-space-before": null,
        "scss/operator-no-unspaced": true
    }
};
