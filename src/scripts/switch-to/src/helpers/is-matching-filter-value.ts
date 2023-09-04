/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

export function isMatchingFilterValue(s: string | null, keywords: string): boolean {
    if (!s) {
        return false;
    }

    const lower_case_keywords_as_string = keywords.toLowerCase();
    const lower_case_keywords = lower_case_keywords_as_string
        .split(" ")
        .filter((keyword) => keyword);

    const lower_case_string = s.toLowerCase();

    if (lower_case_keywords.length === 0) {
        return true;
    } else if (lower_case_keywords.length === 1) {
        return lower_case_string.indexOf(lower_case_keywords[0]) !== -1;
    }

    const regex = new RegExp(
        "(?:^|\\W)(?:" +
            lower_case_keywords.map((keyword) => escape(keyword)).join("|") +
            ")(?:\\W|$)",
    );

    return regex.test(lower_case_string);
}

// See https://262.ecma-international.org/7.0/#prod-SyntaxCharacter
const REGEXP_SPECIAL_CHARS = /[\\^$.*+?()[\]{}|]/g;

function escape(s: string): string {
    return s.replace(REGEXP_SPECIAL_CHARS, "\\$&");
}
