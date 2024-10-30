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

import { describe, expect, it } from "vitest";
import type { EditorState } from "prosemirror-state";
import { DOMParser } from "prosemirror-model";
import { getSelectionThatWrapsAllSelectedLinks } from "./get-selection-that-wraps-all-selected-links";
import { createLocalDocument } from "../../../helpers";
import { buildCustomSchema } from "../../../custom_schema";

const custom_schema = buildCustomSchema();

describe("getSelectionThatWrapsAllSelectedLinks", () => {
    describe("When the user selects a part of a link", () => {
        it("should return a selection that includes the entire link", () => {
            const local_document: Document = createLocalDocument();
            const editor_content = local_document.createElement("div");
            editor_content.innerHTML =
                "<a href='https://www.google.com/'>first link</a> some text <a href='https://www.google.com/'>second link</a>";
            const state = {
                doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
                schema: custom_schema,
                selection: {
                    $from: {
                        pos: 1,
                    },
                    $to: {
                        pos: 2,
                    },
                },
            } as unknown as EditorState;

            const first_link_position = {
                start: 1,
                end: 11,
            };

            expect(getSelectionThatWrapsAllSelectedLinks(state)).toEqual(first_link_position);
        });
    });
    describe("When the user just clicks on the link", () => {
        it("should return a selection that includes the entire link", () => {
            const local_document: Document = createLocalDocument();
            const editor_content = local_document.createElement("div");
            editor_content.innerHTML =
                "<a href='https://www.google.com/'>first link</a> some text <a href='https://www.google.com/'>second link</a>";
            const state = {
                doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
                schema: custom_schema,
                selection: {
                    $from: {
                        pos: 30,
                    },
                    $to: {
                        pos: 30,
                    },
                },
            } as unknown as EditorState;

            const second_link_position = {
                start: 22,
                end: 33,
            };

            expect(getSelectionThatWrapsAllSelectedLinks(state)).toEqual(second_link_position);
        });
    });
    describe("When the user selects part of two links", () => {
        it("should return a selection that includes the two links", () => {
            const local_document: Document = createLocalDocument();
            const editor_content = local_document.createElement("div");
            editor_content.innerHTML =
                "some text before <a href='https://www.google.com/'>first link</a> some text <a href='https://www.google.com/'>second link</a> some text after";
            const first_link = {
                start: 18,
                end: 28,
            };
            const second_link = {
                start: 39,
                end: 50,
            };
            const state = {
                doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
                schema: custom_schema,
                selection: {
                    $from: {
                        pos: first_link.start + 1,
                    },
                    $to: {
                        pos: second_link.end - 1,
                    },
                },
            } as unknown as EditorState;

            const selection_of_two_links = {
                start: first_link.start,
                end: second_link.end,
            };

            expect(getSelectionThatWrapsAllSelectedLinks(state)).toEqual(selection_of_two_links);
        });
    });
});
