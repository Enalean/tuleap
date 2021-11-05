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

interface Diff {
    charset: string;
    lines: ReadonlyArray<{
        content: string;
    }>;
}

export function doesChangedCodeContainsPotentiallyDangerousBidirectionalUnicodeText(
    diff: Readonly<Diff>
): boolean {
    if (diff.charset === "binary") {
        return false;
    }
    return diff.lines.some((line) =>
        doesTextContentPotentiallyDangerousBidirectionalUnicodeText(line.content)
    );
}

const UNICODE_POTENTIALLY_DANGEROUS_BIDIRECTIONAL_CHARACTERS = [
    "\u202a",
    "\u202b",
    "\u202d",
    "\u202e",
    "\u2066",
    "\u2067",
    "\u2068",
    "\u202c",
    "\u2069",
];

function doesTextContentPotentiallyDangerousBidirectionalUnicodeText(text: string): boolean {
    return UNICODE_POTENTIALLY_DANGEROUS_BIDIRECTIONAL_CHARACTERS.some((character) =>
        text.includes(character)
    );
}
