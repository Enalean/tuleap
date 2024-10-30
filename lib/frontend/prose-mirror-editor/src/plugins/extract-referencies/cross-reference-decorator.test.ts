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

import { DOMParser } from "prosemirror-model";
import { expect, describe, it } from "vitest";
import { EditorState } from "prosemirror-state";
import { createLocalDocument } from "../../helpers";
import { buildCustomSchema } from "../../custom_schema";
import type { Decoration, DecorationSet } from "prosemirror-view";
import { CROSS_REFERENCE_DECORATION_TYPE } from "../../helpers/create-cross-reference-decoration";
import { ReferencePositionComputerStub } from "./helpers/stubs/ReferencePositionComputerStub";
import { NodePositionContainingReferenceFinderStub } from "./helpers/stubs/NodePositionContainingReferenceFinderStub";
import { CrossReferencesDecorator } from "./cross-references-decorator";

describe("node decorator", () => {
    let state: EditorState;
    const local_document: Document = createLocalDocument();
    function initEditorWithText(text: string): void {
        const editor_content = local_document.createElement("div");
        const custom_schema = buildCustomSchema();

        editor_content.innerHTML = text;
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
        });
    }

    it("decorate link of node", () => {
        initEditorWithText("<p>a text with art #1</p>");
        const node = state.doc;

        const references = [
            {
                text: "art #1",
                link: "https://example.com?goto=1",
                context: "",
            },
        ];

        const position = { from: 16, to: 23 };

        const first_ref = {
            from: position.from,
            to: position.to,
            type: {
                attrs: {
                    class: "cross-reference-link",
                    "data-href": "https://example.com?goto=1",
                },
                spec: {
                    type: CROSS_REFERENCE_DECORATION_TYPE,
                },
            },
        } as unknown as Decoration;

        const result: DecorationSet = CrossReferencesDecorator(
            ReferencePositionComputerStub.withPosition(position),
            NodePositionContainingReferenceFinderStub.withPositions([1]),
        ).decorateCrossReference(node, references);
        expect(result.find()).toEqual([first_ref]);
    });
});
