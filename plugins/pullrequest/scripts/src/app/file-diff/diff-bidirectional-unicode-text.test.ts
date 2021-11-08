/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import {
    doesChangedCodeContainsPotentiallyDangerousBidirectionalUnicodeText,
    getCodeMirrorConfigurationToMakePotentiallyDangerousBidirectionalCharactersVisible,
} from "./diff-bidirectional-unicode-text";

describe("diff-bidirectional-unicode-text", () => {
    it("detects text with potentially dangerous unicode bidirectional characters", () => {
        const is_potentially_dangerous =
            doesChangedCodeContainsPotentiallyDangerousBidirectionalUnicodeText({
                charset: "utf-8",
                lines: [{ content: "A\u202bB" }],
            });

        expect(is_potentially_dangerous).toBe(true);
    });

    it("does not consider binary content to be dangerous", () => {
        const is_potentially_dangerous =
            doesChangedCodeContainsPotentiallyDangerousBidirectionalUnicodeText({
                charset: "binary",
                lines: [{ content: "A\u202bB" }],
            });

        expect(is_potentially_dangerous).toBe(false);
    });

    it("does not consider text without BiDi characters to be dangerous", () => {
        const is_potentially_dangerous =
            doesChangedCodeContainsPotentiallyDangerousBidirectionalUnicodeText({
                charset: "binary",
                lines: [{ content: "AB" }],
            });

        expect(is_potentially_dangerous).toBe(false);
    });

    it("makes CodeMirror highlights potentially dangerous BiDi characters", () => {
        const codemirror_options =
            getCodeMirrorConfigurationToMakePotentiallyDangerousBidirectionalCharactersVisible({
                specialChars: /a/,
            });

        expect(codemirror_options.specialChars).toBeDefined();
        expect((codemirror_options.specialChars as RegExp).test("\u202a")).toBe(true);
        expect((codemirror_options.specialChars as RegExp).test("a")).toBe(true);
        expect((codemirror_options.specialChars as RegExp).test("b")).toBe(false);
    });
});
