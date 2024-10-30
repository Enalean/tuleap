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

import { describe, expect, it } from "vitest";
import type { EditorNode } from "../../../types/internal-types";
import { PositionsInDescendentsFinder } from "./DescendentsContainingReferenceFinder";
import { DOMParser } from "prosemirror-model";
import { buildCustomSchema } from "../../../custom_schema";
import { createLocalDocument } from "../../../helpers";

function buildEditorNode(local_document: Document, content: string): EditorNode {
    const editor_content = local_document.createElement("div");
    editor_content.innerHTML = content;
    return DOMParser.fromSchema(buildCustomSchema()).parse(editor_content);
}

describe("reference position finder", () => {
    const local_document: Document = createLocalDocument();
    const REFERENCE = "art #1";
    it("returns an empty array when child node is not Text", () => {
        const node = buildEditorNode(local_document, "<img src='https://example.com' alt='img'>");

        expect(
            PositionsInDescendentsFinder().findPositionsContainingReference(node, REFERENCE),
        ).toEqual([]);
    });

    it("returns an empty array when node does not include reference", () => {
        const node = buildEditorNode(local_document, "This is a text");

        expect(
            PositionsInDescendentsFinder().findPositionsContainingReference(node, REFERENCE),
        ).toEqual([]);
    });

    it("returns an the node position in prose mirror when reference is found", () => {
        const node = buildEditorNode(
            local_document,
            "<p><ul><li>This is a text and a ref art #1</li></ul></p><br><p>art #1</p>",
        );

        const text_positions = PositionsInDescendentsFinder().findPositionsContainingReference(
            node,
            REFERENCE,
        );
        expect(text_positions.length).toEqual(2);
        expect(text_positions).toEqual([5, 45]);
    });
});
