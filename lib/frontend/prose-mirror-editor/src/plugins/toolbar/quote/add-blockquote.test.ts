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
import { EditorState, NodeSelection } from "prosemirror-state";
import { EditorView } from "prosemirror-view";
import { DOMParser } from "prosemirror-model";
import type { DOMOutputSpec } from "../../../types";
import { prosemirror_nodes } from "../../../types";
import { buildCustomSchema } from "../../../custom_schema";
import { createLocalDocument } from "../../../helpers";
import { addBlockQuote } from "./add-blockquote";
import { STRUCTURE_BLOCK_GROUP } from "../../../helpers/isNodeAStructureBlock";

describe("addBlockquote", () => {
    it("Should wrap only wrappable nodes in a <blockquote/> element", () => {
        const extended_schema = buildCustomSchema({
            external_node_to_ignore: {
                content: "block+",
                toDOM(): DOMOutputSpec {
                    return ["external-node", 0];
                },
                parseDOM: [{ tag: "external-node" }],
                group: STRUCTURE_BLOCK_GROUP,
            },
            ...prosemirror_nodes,
            doc: {
                content: "external_node_to_ignore",
            },
        });

        const html_doc = createLocalDocument();
        const content = html_doc.createElement("div");

        content.insertAdjacentHTML(
            "afterbegin",
            `
            <external-node>
                <p>Wrap me please :S</p>
            </external-node>
        `,
        );

        const doc = DOMParser.fromSchema(extended_schema).parse(content);
        const state = EditorState.create({
            doc,
            schema: extended_schema,
            selection: NodeSelection.create(doc, 1), // Position 1 points on the <external-node> open tag
        });

        const view = new EditorView(html_doc.createElement("div"), { state });

        addBlockQuote(state, view.dispatch);

        expect(view.dom.innerHTML).toMatchInlineSnapshot(`
            <external-node>
              <blockquote>
                <p class="ProseMirror-selectednode" draggable="true">Wrap me please :S</p>
              </blockquote>
            </external-node>
        `);
    });
});
