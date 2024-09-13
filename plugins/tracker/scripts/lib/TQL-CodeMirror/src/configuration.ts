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
import syntax from "@tuleap/tql-syntax";

export const variable_definition = {
    regex: syntax.variable.pattern,
    token: "tql-variable",
};

const TQL_mode_definition = {
    start: [
        {
            regex: syntax.double_quote_string.pattern,
            token: "tql-string",
        },
        {
            regex: syntax.simple_quote_string.pattern,
            token: "tql-string",
        },
        {
            regex: syntax.time_period.pattern,
            token: "tql-time-period",
        },
        {
            regex: syntax.number.pattern,
            token: "tql-number",
        },
        {
            regex: syntax.structure.pattern,
            token: "tql-structure",
        },
        {
            regex: syntax.linked_from.pattern,
            token: "tql-keyword",
        },
        {
            regex: syntax.function.pattern,
            token: "tql-keyword",
        },
        {
            regex: syntax.operator.pattern,
            token: "tql-operator",
        },
        {
            regex: /[(]/,
            token: "tql-operator",
            indent: true,
        },
        {
            regex: /[)]/,
            token: "tql-operator",
            dedent: true,
        },
        {
            regex: syntax.atvariable.pattern,
            token: "tql-atvariable",
        },
        { ...variable_definition },
    ],
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
