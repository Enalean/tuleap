/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, type MockInstance, vi } from "vitest";
import { EditorState, TextSelection } from "prosemirror-state";
import { EditorView } from "prosemirror-view";
import { buildCustomSchema, buildToolbarBus } from "@tuleap/prose-mirror-editor";
import { artidoc_editor_schema } from "./artidoc-editor-schema";
import { JumpToSectionNodePlugin } from "./jump-to-section-node";

// Note:
// <as> = <artidoc-section>
// <ast> = <artidoc-section-title>
// <asd> = <artidoc-section-description>

// Positioning:
// 0    1     2 3 4 5 6 7 8 9 10 11      12     13   14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29    30     31      32
//  <as> <ast> T h e _ t i t l  e  </ast>  <asd>  <p>  T  h  e  _  d  e  s  c  r  i  p  t  i  o  n  </p>  </asd>  </as>

describe("jump-to-section-node", () => {
    const schema = buildCustomSchema(artidoc_editor_schema);
    const toolbar_bus = buildToolbarBus();
    const doc = schema.node("doc", null, [
        schema.node("artidoc_section", null, [
            schema.node("artidoc_section_title", null, schema.text("The title")),
            schema.node("artidoc_section_description", null, [
                schema.node("paragraph", null, schema.text("The description")),
            ]),
        ]),
    ]);

    const state = EditorState.create({
        doc,
        schema,
        plugins: [JumpToSectionNodePlugin(toolbar_bus)],
    });

    const editor_element = document.createElement("div");
    document.body.appendChild(editor_element);

    const view = new EditorView(editor_element, { state });

    const backspace_event = new KeyboardEvent("keydown", { key: "Backspace" });
    const enter_event = new KeyboardEvent("keydown", { key: "Enter" });

    const END_OF_TITLE_POSITION = 11;
    const SOMEWHERE_IN_THE_TITLE_POSITION = 7; // [2;11]
    const START_OF_DESCRIPTION_POSITION = 14;
    const SOMEWHERE_IN_THE_DESCRIPTION_POSITION = 18; // [14;29]

    const setCursorPosition = (position: number): void => {
        view.dispatch(view.state.tr.setSelection(TextSelection.create(view.state.doc, position)));
    };

    let enableToolbar: MockInstance;
    let disableToolbar: MockInstance;

    beforeEach(() => {
        enableToolbar = vi.spyOn(toolbar_bus, "enableToolbar");
        disableToolbar = vi.spyOn(toolbar_bus, "disableToolbar");
    });

    describe("When Enter is pressed anywhere in the title", () => {
        beforeEach(() => {
            setCursorPosition(SOMEWHERE_IN_THE_TITLE_POSITION);
            view.dom.dispatchEvent(enter_event);
        });

        it("should moves the cursor to the beginning of the description", () => {
            expect(view.state.selection.from).toBe(START_OF_DESCRIPTION_POSITION);
        });

        it("should enable the toolbar", () => {
            expect(enableToolbar).toHaveBeenCalledOnce();
        });
    });

    describe("When Backspace is pressed at the beginning of the description", () => {
        beforeEach(() => {
            setCursorPosition(START_OF_DESCRIPTION_POSITION);
            view.dom.dispatchEvent(backspace_event);
        });

        it("should moves the cursor to the end of the title", () => {
            expect(view.state.selection.from).toBe(END_OF_TITLE_POSITION);
        });

        it("should disable the toolbar", () => {
            expect(disableToolbar).toHaveBeenCalled();
        });
    });

    describe("When Backspace is pressed elsewhere than at the beginning of the description", () => {
        beforeEach(() => {
            setCursorPosition(SOMEWHERE_IN_THE_DESCRIPTION_POSITION);
            view.dom.dispatchEvent(backspace_event);
        });

        it("should not moves the cursor to the end of the title", () => {
            expect(view.state.selection.from).not.toBe(END_OF_TITLE_POSITION);
        });

        it("should not disable the toolbar", () => {
            expect(disableToolbar).not.toHaveBeenCalled();
        });
    });
});
