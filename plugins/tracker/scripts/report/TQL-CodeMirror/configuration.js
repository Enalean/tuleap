/**
 * Copyright (c) 2017 - 2018, Enalean. All rights reserved
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
            regex: /(?:now|between|in|not|myself|open)\b/i,
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

const variable_definition = {
    regex: /@?[a-zA-Z0-9_]+/,
    token: "variable",
};

function buildModeDefinition({ additional_keywords = [] }) {
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

const TQL_autocomplete_keywords = [
    "AND",
    "OR",
    "BETWEEN(",
    "NOW()",
    "IN(",
    "NOT",
    "MYSELF()",
    "@comments",
];

export { buildModeDefinition, TQL_autocomplete_keywords };
