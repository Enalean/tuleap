/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import { describe, expect, it, vi } from "vitest";
import { EditorState, NodeSelection, TextSelection } from "prosemirror-state";
import { DOMParser } from "prosemirror-model";
import { custom_schema } from "../custom_schema";
import { setupToolbar } from "../plugins/toolbar";
import { createLocalDocument, gettext_provider } from "./helper-for-test";
import { removeLink } from "./remove-link";
import { schema } from "prosemirror-schema-basic";

describe("remove links", () => {
    let state: EditorState;
    const local_document: Document = createLocalDocument();
    const dispatch = vi.fn();

    it("should remove link when user click on text link", () => {
        const editor_content = local_document.createElement("div");
        editor_content.innerHTML = "standard text with <a href='https://example.com'>link</a>";
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
            ...setupToolbar(gettext_provider, "1"),
        });

        state.tr.setSelection(new NodeSelection(state.doc.resolve(22)));

        removeLink(state, schema.marks.link, dispatch);

        expect(state.doc.rangeHasMark(0, state.doc.textContent.length, schema.marks.link)).toBe(
            false,
        );
    });

    it("should remove link when user select part of text link", () => {
        const editor_content = local_document.createElement("div");
        editor_content.innerHTML = "standard text with <a href='https://example.com'>link</a>";
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
            ...setupToolbar(gettext_provider, "1"),
        });

        state.tr.setSelection(new TextSelection(state.doc.resolve(22), state.doc.resolve(24)));

        expect(state.doc.rangeHasMark(0, state.doc.textContent.length, schema.marks.link)).toBe(
            false,
        );
    });
});
