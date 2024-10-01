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
import { decorateLink } from "./link-decorator";
import { expect, describe, it } from "vitest";
import { EditorState } from "prosemirror-state";
import { createLocalDocument } from "../../helpers";
import { custom_schema } from "../../custom_schema";
import type { Decoration, DecorationSet } from "prosemirror-view";
import { CROSS_REFERENCE_DECORATION_TYPE } from "../../helpers/create-cross-reference-decoration";

describe("node decorator", () => {
    let state: EditorState;
    const local_document: Document = createLocalDocument();
    function initEditorWithText(text: string): void {
        const editor_content = local_document.createElement("div");
        editor_content.innerHTML = text;
        state = EditorState.create({
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
            schema: custom_schema,
        });
    }

    it("decorate links of node", () => {
        initEditorWithText("<p>a text with <ul><li>art #1</li><li>and art #2</li></p>");
        const node = state.doc;

        const references = [
            {
                text: "art #1",
                link: "https://example.com?goto=1",
            },
            {
                text: "art #2",
                link: "https://example.com?goto=2",
            },
        ];

        const first_ref = {
            from: 16,
            to: 23,
            type: {
                attrs: {
                    class: "cross-reference-link",
                    "data-href": "https://example.com?goto=1",
                },
                spec: {
                    type: CROSS_REFERENCE_DECORATION_TYPE,
                },
            },
        } as unknown as DecorationSet;

        const second_ref = {
            from: 30,
            to: 37,
            type: {
                attrs: {
                    class: "cross-reference-link",
                    "data-href": "https://example.com?goto=2",
                },
                spec: {
                    type: CROSS_REFERENCE_DECORATION_TYPE,
                },
            } as unknown as Decoration,
        } as unknown as DecorationSet;

        const result: DecorationSet = decorateLink(node, references);
        expect(result.find()).toEqual([first_ref, second_ref]);
    });
});
