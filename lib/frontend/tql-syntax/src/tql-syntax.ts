/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

const SYNTAX = {
    comment: {
        pattern: /\/{2}.*/,
    },
    simple_quote_string: {
        pattern: /'(?:[^\\]|\\.)*?(?:'|$)/,
    },
    double_quote_string: {
        pattern: /"(?:[^\\]|\\.)*?(?:"|$)/,
    },
    time_period: {
        pattern: /\d+[dwmy]/i,
    },
    number: {
        pattern: /\d+(?:\.\d+)?/,
    },
    structure: {
        pattern: /(?:and|from|or|select|where|order\s*by)\b/i,
    },
    function: {
        pattern:
            /(?:artifact|between|by|child|children|covered|covering|from|in|is|linked|myself|not|now|open|parent|to|tracker|type|with|without|linked\s*from|asc|ascending|desc|descending|my_projects)\b/i,
    },
    operator: {
        pattern: /[=<>!+-]+/,
    },
    parenthesis: {
        pattern: /[()]/,
    },
    atvariable: {
        pattern: /@[.\w-]+/,
    },
    variable: {
        pattern: /[.\w-]+/,
    },
};

export default SYNTAX;
