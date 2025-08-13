/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { match_emoji_regexp } from "./regexps";

const valid_inputs: string[][] = [[":hello:"], [":+1:"], [":flag-fr:"], [":some_underscore:"]];
const invalid_inputs: string[][] = [
    ["foo"],
    [":with spaces:"],
    [":with inv@lid char:"],
    ["Some middly long sentence with words and numbers 123"],
];

describe("regexps", () => {
    describe("match_emoji_regexp", () => {
        it.each(valid_inputs)("Should match '%s'", (input: string) => {
            expect(input).toMatch(match_emoji_regexp);
        });

        it.each(invalid_inputs)("Should not match '%s'", (input: string) => {
            expect(input).not.toMatch(match_emoji_regexp);
        });
    });
});
