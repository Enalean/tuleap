/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, beforeEach, expect, it } from "vitest";
import type { CheckIsMarkTypeRepeatedInSelection } from "./IsMarkTypeRepeatedInSelectionChecker";
import { IsMarkTypeRepeatedInSelectionChecker } from "./IsMarkTypeRepeatedInSelectionChecker";
import { custom_schema } from "../custom_schema";
import type { EditorState } from "prosemirror-state";
import { createLocalDocument } from "./helper-for-test";
import { DOMParser } from "prosemirror-model";

describe("IsMarkTypeRepeatedInSelectionChecker", () => {
    let checker: CheckIsMarkTypeRepeatedInSelection;

    beforeEach(() => {
        checker = IsMarkTypeRepeatedInSelectionChecker();
    });

    describe("When there is one occurrence of the mark in the selection", () => {
        it("should return false", () => {
            const local_document: Document = createLocalDocument();
            const editor_content = local_document.createElement("div");
            editor_content.innerHTML = "<a href='https://www.google.com/'>standard link</a>";
            const state = {
                doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
                schema: custom_schema,
                selection: {
                    from: 1,
                    to: 10,
                },
            } as unknown as EditorState;
            expect(checker.isMarkTypeRepeatedInSelection(state, state.schema.marks.link)).toBe(
                false,
            );
        });
    });
    describe("When there are two occurrences of the mark in the selection", () => {
        it("should return true", () => {
            const local_document: Document = createLocalDocument();
            const editor_content = local_document.createElement("div");
            editor_content.innerHTML =
                '<h1><a href="https://www.google.com" title="first">first link</a> <a href="https://www.google.com" title="two">second link</a></h1>';
            const state = {
                doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
                schema: custom_schema,
                selection: {
                    from: 1,
                    to: 20,
                },
            } as unknown as EditorState;
            expect(checker.isMarkTypeRepeatedInSelection(state, state.schema.marks.link)).toBe(
                true,
            );
        });
    });
});
