/**
 * Copyright (c) Enalean 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

const TQL_mode_definition = {
    start: [
        {
            regex: /"(?:[^\\]|\\.)*?(?:"|$)/, // double quotes
            token: "string",
        },
        {
            regex: /'(?:[^\\]|\\.)*?(?:'|$)/, // single quotes
            token: "string",
        },
        {
            regex: /\d+[dwmy]/i, // Time period
            token: "variable-3",
        },
        {
            regex: /\d+(?:\.\d+)?/i, // Float & integers
            token: "number",
        },
        {
            regex: /(?:and|or)\b/i,
            token: "keyword",
        },
        {
            regex: /(?:select|where|now|between|in|not|myself|open|parent|artifact|tracker|with|without|children|child|is|linked|from|to|type|covering|covered|by)\b/i,
            token: "variable-2",
        },
        {
            regex: /[=<>!+-]+/,
            token: "operator",
        },
        {
            regex: /[(]/,
            token: "operator",
            indent: true,
        },
        {
            regex: /[)]/,
            token: "operator",
            dedent: true,
        },
    ],
};

export const variable_definition = {
    regex: /@?[a-zA-Z0-9_-]+/,
    token: "variable",
};

export type TQLDefinition = typeof TQL_mode_definition;

export function buildModeDefinition({
    additional_keywords = [],
}: {
    additional_keywords: string[];
}): TQLDefinition {
    if (additional_keywords.length > 0) {
        const keywords_regex = additional_keywords.join("|");
        const additional_keywords_definition = {
            regex: new RegExp("(?:" + keywords_regex + ")\\b", "i"),
            token: "variable-2",
        };
        TQL_mode_definition.start.push(additional_keywords_definition);
    }
    TQL_mode_definition.start.push(variable_definition);
    return TQL_mode_definition;
}

export const TQL_autocomplete_keywords = [
    "AND",
    "OR",
    "BETWEEN(",
    "NOW()",
    "IN(",
    "NOT",
    "MYSELF()",
    "WITH PARENT",
    "WITH PARENT ARTIFACT",
    "WITH PARENT TRACKER",
    "WITH TYPE",
    "WITHOUT PARENT",
    "WITHOUT PARENT ARTIFACT",
    "WITHOUT PARENT TRACKER",
    "WITH CHILDREN",
    "WITH CHILDREN ARTIFACT",
    "WITH CHILDREN TRACKER",
    "WITHOUT CHILDREN",
    "WITHOUT CHILDREN ARTIFACT",
    "WITHOUT CHILDREN TRACKER",
    "IS LINKED FROM",
    "IS LINKED FROM ARTIFACT",
    "IS LINKED FROM TRACKER",
    "IS NOT LINKED FROM",
    "IS NOT LINKED FROM ARTIFACT",
    "IS NOT LINKED FROM TRACKER",
    "IS LINKED TO",
    "IS LINKED TO ARTIFACT",
    "IS LINKED TO TRACKER",
    "IS NOT LINKED TO",
    "IS NOT LINKED TO ARTIFACT",
    "IS NOT LINKED TO TRACKER",
    "IS COVERED",
    "IS COVERED BY ARTIFACT",
    "IS NOT COVERED",
    "IS NOT COVERED BY ARTIFACT",
    "IS COVERING",
    "IS COVERING ARTIFACT",
    "IS NOT COVERING",
    "IS NOT COVERING ARTIFACT",
    "@comments",
];
