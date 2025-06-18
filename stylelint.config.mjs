/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import fs from "node:fs";

const prettier_config = JSON.parse(fs.readFileSync("./.prettierrc", { encoding: "utf-8" }));

export default {
    extends: [
        "stylelint-config-recommended",
        "stylelint-config-sass-guidelines",
        "stylelint-config-property-sort-order-smacss",
        "stylelint-config-standard-scss",
        "stylelint-config-recommended-vue/scss",
        "stylelint-config-html/vue",
        "stylelint-prettier/recommended",
    ],
    reportNeedlessDisables: true,
    reportInvalidScopeDisables: true,
    reportUnscopedDisables: true,
    rules: {
        "comment-word-disallowed-list": [
            [/^!/],
            {
                message: `Never use the "/*!" style of comments. Those comments are output in compressed CSS. (comment-word-disallowed-list)`,
            },
        ],
        "color-hex-length": ["long"],
        "color-named": [
            "never",
            {
                message: "Colors should be written in hexadecimal format (color-named)",
            },
        ],
        "declaration-block-no-duplicate-properties": true,
        "function-calc-no-unspaced-operator": true,
        "max-nesting-depth": [4],
        "no-duplicate-selectors": true,
        "selector-id-pattern": [
            "^[a-z0-9\\-]+$",
            {
                message:
                    "Selector should be written in lowercase with hyphens (selector-id-pattern)",
            },
        ],
        "selector-max-compound-selectors": [5],
        "selector-max-id": null,
        "selector-no-qualifying-type": [
            true,
            {
                ignore: ["attribute"],
            },
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
            "fr",
        ],
        "media-feature-range-notation": "prefix",
        // Disabled rules because already handled by Prettier
        "@stylistic/block-opening-brace-space-before": null,
        "@stylistic/color-hex-case": null,
        "@stylistic/declaration-bang-space-after": null,
        "@stylistic/declaration-bang-space-before": null,
        "@stylistic/declaration-block-semicolon-newline-after": null,
        "@stylistic/declaration-block-semicolon-space-before": null,
        "@stylistic/declaration-block-trailing-semicolon": null,
        "@stylistic/declaration-colon-space-after": null,
        "@stylistic/declaration-colon-space-before": null,
        "@stylistic/function-comma-space-after": null,
        "@stylistic/function-parentheses-space-inside": null,
        "@stylistic/indentation": null,
        "@stylistic/media-feature-parentheses-space-inside": null,
        "@stylistic/no-missing-end-of-source-newline": null,
        "@stylistic/number-leading-zero": null,
        "@stylistic/number-no-trailing-zeros": null,
        "@stylistic/selector-list-comma-newline-after": null,
        "@stylistic/string-quotes": null,
        "scss/dollar-variable-colon-space-before": null,
        "scss/operator-no-newline-after": null,
        // Plugins
        "order/properties-alphabetical-order": null, // Overridden by stylelint-config-property-sort-order-smacss
        "scss/at-else-empty-line-before": ["never"],
        "scss/operator-no-unspaced": true,
        "scss/partial-no-import": true,
        "font-family-no-missing-generic-family-keyword": null,
        "no-descending-specificity": null, // Need a lot of work with existing files
        "no-invalid-position-at-import-rule": null, // Need work with existing files
        "prettier/prettier": [true, prettier_config],
    },
};
