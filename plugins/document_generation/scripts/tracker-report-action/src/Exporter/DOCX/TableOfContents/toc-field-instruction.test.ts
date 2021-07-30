/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { TOCFieldInstruction } from "./toc-field-instruction";
import type { IContext } from "docx";
import { StyleLevel } from "docx";

describe("TOC field instruction", () => {
    it("creates TOC field instruction with all possible options", () => {
        const toc_field_instruction = new TOCFieldInstruction({
            captionLabel: "A",
            entriesFromBookmark: "B",
            captionLabelIncludingNumbers: "C",
            sequenceAndPageNumbersSeparator: "D",
            tcFieldIdentifier: "F",
            hyperlink: true,
            tcFieldLevelRange: "L",
            pageNumbersEntryLevelsRange: "N",
            headingStyleRange: "O",
            entryAndPageNumberSeparator: "P",
            seqFieldIdentifierForPrefix: "S",
            stylesWithLevels: [new StyleLevel("SL", 123)],
            useAppliedParagraphOutlineLevel: true,
            preserveTabInEntries: true,
            preserveNewLineInEntries: true,
            hideTabAndPageNumbersInWebView: true,
        });
        const tree = toc_field_instruction.prepForXml({} as IContext);

        expect(tree).toStrictEqual({
            "w:instrText": [
                {
                    _attr: {
                        "xml:space": "default",
                    },
                },
                'TOC \\a "A" \\b "B" \\c "C" \\d "D" \\f "F" \\h \\l "L" \\n "N" \\o "O" \\p "P" \\s "S" \\t "SL,123" \\u \\w \\x \\z',
            ],
        });
    });
});
