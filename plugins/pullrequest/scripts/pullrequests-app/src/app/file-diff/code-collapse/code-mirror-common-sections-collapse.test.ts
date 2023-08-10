/*
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

import type { Editor } from "codemirror";
import {
    collapseCommonSectionsSideBySide,
    collapseCommonSectionsUnidiff,
} from "./code-mirror-common-sections-collapse";
import { setCatalog } from "../../gettext-catalog";

describe("code-mirror-common-sections-collapse", () => {
    let doc: Document;

    beforeEach(() => {
        setCatalog({ getPlural: (nb, msgid) => msgid });

        doc = document.implementation.createHTMLDocument();
    });

    describe("unified diff", () => {
        it(`Given a collection of collapsible sections
            Then it should:
            - replace the lines inside the section with a span
            - unfold the lines of the section when clicked the span is clicked`, () => {
            const sections = [{ start: 10, end: 20 }];

            const markText = jest.fn();
            const marker = {
                clear: jest.fn(),
            };
            markText.mockReturnValue(marker);

            const getLine = jest.fn();

            getLine.mockReturnValue({ length: 150 });

            const editor = {
                name: "unidiff-code-mirror",
                markText,
                getLine,
            } as unknown as Editor;

            const collapsed_label = document.createElement("span");

            jest.spyOn(doc, "createElement").mockReturnValue(collapsed_label);

            collapseCommonSectionsUnidiff(doc, editor, sections);

            expect(editor.markText).toHaveBeenCalledWith(
                { line: 10, ch: 0 },
                { line: 20, ch: 150 },
                { replacedWith: collapsed_label }
            );

            collapsed_label.dispatchEvent(new Event("click"));

            expect(marker.clear).toHaveBeenCalledTimes(1);
        });
    });

    describe("side-by-side diff", () => {
        it(`Given a collection of synchronized collapsible sections
            Then it should:
            - replace the lines inside the sections with spans in both editors
            - when one of the spans is clicked, the targeted synchronized sections are unfolded in both editors`, () => {
            const synchronized_sections = [
                {
                    left: { start: 10, end: 20 },
                    right: { start: 25, end: 35 },
                },
            ];

            const left_editor_collapsed_label = document.createElement("span");
            const right_editor_collapsed_label = document.createElement("span");

            jest.spyOn(doc, "createElement").mockReturnValueOnce(left_editor_collapsed_label);
            jest.spyOn(doc, "createElement").mockReturnValueOnce(right_editor_collapsed_label);

            const left_editor_marker = {
                clear: jest.fn(),
                replacedWith: left_editor_collapsed_label,
            };
            const right_editor_marker = {
                clear: jest.fn(),
                replacedWith: right_editor_collapsed_label,
            };

            const left_codemirror = {
                name: "left-code-mirror",
                markText: jest.fn().mockReturnValue(left_editor_marker),
                getLine: jest.fn().mockReturnValue({ length: 150 }),
                getAllMarks: jest.fn().mockReturnValue([left_editor_marker]),
            } as unknown as Editor;

            const right_codemirror = {
                name: "right-code-mirror",
                markText: jest.fn().mockReturnValue(right_editor_marker),
                getLine: jest.fn().mockReturnValue({ length: 150 }),
                getAllMarks: jest.fn().mockReturnValue([right_editor_marker]),
            } as unknown as Editor;

            collapseCommonSectionsSideBySide(
                doc,
                left_codemirror,
                right_codemirror,
                synchronized_sections
            );

            expect(left_codemirror.markText).toHaveBeenCalledWith(
                { line: 10, ch: 0 },
                { line: 20, ch: 150 },
                { replacedWith: left_editor_collapsed_label }
            );
            expect(right_codemirror.markText).toHaveBeenCalledWith(
                { line: 25, ch: 0 },
                { line: 35, ch: 150 },
                { replacedWith: right_editor_collapsed_label }
            );

            right_editor_collapsed_label.dispatchEvent(new Event("click"));

            expect(left_editor_marker.clear).toHaveBeenCalledTimes(1);
            expect(right_editor_marker.clear).toHaveBeenCalledTimes(1);
        });
    });
});
