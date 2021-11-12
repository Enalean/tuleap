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

import CodeMirror from "codemirror";
import potentially_dangerous_bidirectional_characters from "../../../../../../src/common/Code/potentially-dangerous-bidirectional-characters.json";

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

function doesTextContentPotentiallyDangerousBidirectionalUnicodeText(text: string): boolean {
    return potentially_dangerous_bidirectional_characters.some((character) =>
        text.includes(character)
    );
}

export function getCodeMirrorConfigurationToMakePotentiallyDangerousBidirectionalCharactersVisible(
    config: Readonly<CodeMirror.EditorConfiguration>
): CodeMirror.EditorConfiguration {
    const special_chars_current: RegExp = config.specialChars || CodeMirror.defaults.specialChars;
    const regex_potentially_dangerous_bidirectional_characters = new RegExp(
        "[" + potentially_dangerous_bidirectional_characters.join("") + "]"
    );

    return {
        ...config,
        specialChars: new RegExp(
            "(?:" +
                special_chars_current.source +
                ")|(?:" +
                regex_potentially_dangerous_bidirectional_characters.source +
                ")",
            special_chars_current.flags
        ),
    };
}
