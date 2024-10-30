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

import { beforeEach, describe, expect, it } from "vitest";
import { EditorState } from "prosemirror-state";
import { createLocalDocument } from "../../../helpers";
import { DOMParser } from "prosemirror-model";
import { buildCustomSchema } from "../../../custom_schema";
import { getWrappingNodeInfo } from "./NodeInfoRetriever";

describe("getWrappingNodeInfo", () => {
    let state: EditorState;
    beforeEach(() => {
        const local_document: Document = createLocalDocument();
        const editor_content = local_document.createElement("div");
        const custom_schema = buildCustomSchema();

        editor_content.innerHTML = "standard text with <a href='https://example.com'>link</a>";
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
        });
    });

    it("returns user selection when no link node is found", () => {
        const cursor_position = state.doc.resolve(1);
        const mark_type = state.schema.marks.link;

        const result = getWrappingNodeInfo(cursor_position, mark_type, state);
        expect(result.is_creating_node).toBe(true);
        expect(result.from).toBe(1);
        expect(result.to).toBe(1);
    });

    it("returns node information when user click inside a link", () => {
        const cursor_position = state.doc.resolve(20);
        const mark_type = state.schema.marks.link;

        const result = getWrappingNodeInfo(cursor_position, mark_type, state);
        expect(result.is_creating_node).toBe(false);
        expect(result.from).toBe(20);
        expect(result.to).toBe(24);
        expect(result.corresponding_node.textContent).toBe("link");
    });
});
