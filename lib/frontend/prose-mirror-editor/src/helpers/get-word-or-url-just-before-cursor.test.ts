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

import { describe, expect, it, vi } from "vitest";
import { getWordOrUrlJustBeforeCursor } from "./get-word-or-url-just-before-cursor";
import type { EditorState } from "prosemirror-state";
import { custom_schema } from "../custom_schema";
import { createLocalDocument } from "./helper-for-test";
import { DOMParser } from "prosemirror-model";

describe("getWordOrUrlJustBeforeCursor", () => {
    describe("when text before cursor is not empty", () => {
        describe("when text just before the cursor is a casual text", () => {
            it("should return the last word before the cursor", () => {
                const local_document: Document = createLocalDocument();
                const editor_content = local_document.createElement("div");
                editor_content.innerHTML =
                    "some text before the https://example1.com cursor| after";
                const state = {
                    doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
                    schema: custom_schema,
                    selection: {
                        $from: {
                            start: vi.fn().mockReturnValue(1),
                            pos: editor_content.innerHTML.length - 6,
                        },
                    },
                } as unknown as EditorState;

                expect(getWordOrUrlJustBeforeCursor(state)).toBe("cursor");
            });
        });
        describe("when text just before cursor is a link", () => {
            it("should return the url just before the cursor", () => {
                const local_document: Document = createLocalDocument();
                const editor_content = local_document.createElement("div");
                editor_content.innerHTML =
                    "some text before the https://example1.com https://example2.com| after";
                const state = {
                    doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
                    schema: custom_schema,
                    selection: {
                        $from: {
                            start: vi.fn().mockReturnValue(1),
                            pos: editor_content.innerHTML.length - 6,
                        },
                    },
                } as unknown as EditorState;

                expect(getWordOrUrlJustBeforeCursor(state)).toBe("https://example2.com");
            });
        });
    });
    describe("when text before cursor is empty", () => {
        it("should return an empty string", () => {
            const local_document: Document = createLocalDocument();
            const editor_content = local_document.createElement("div");
            editor_content.innerHTML = "";
            const state = {
                doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
                schema: custom_schema,
                selection: {
                    $from: {
                        start: vi.fn().mockReturnValue(1),
                        pos: 1,
                    },
                },
            } as unknown as EditorState;

            expect(getWordOrUrlJustBeforeCursor(state)).toBe("");
        });
    });
});
