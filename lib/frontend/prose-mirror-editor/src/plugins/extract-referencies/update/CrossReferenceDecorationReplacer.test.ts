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

import { describe, it, expect } from "vitest";
import { DOMParser } from "prosemirror-model";
import { EditorView } from "prosemirror-view";
import { EditorState } from "prosemirror-state";
import { buildCustomSchema } from "../../../custom_schema";
import { createCrossReferenceDecoration } from "../../../helpers/create-cross-reference-decoration";
import { EditorTextNodeCreator } from "../../../helpers/EditorTextNodeCreator";
import { createLocalDocument } from "../../../helpers";
import { CrossReferenceDecorationReplacer } from "./CrossReferenceDecorationReplacer";

describe("CrossReferenceDecorationReplacer", () => {
    it("Given a decoration and an UpdatedCrossReference, then it should return a transaction to replace it with the new editor node containing the updated reference", () => {
        const editor_content = createLocalDocument().createElement("div");
        editor_content.textContent = "Reference art #123 and art #456";

        const custom_schema = buildCustomSchema();
        const state = EditorState.create({
            schema: custom_schema,
            doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
        });
        const view = new EditorView(editor_content, { state });
        const decoration = createCrossReferenceDecoration(
            { from: 11, to: 20 },
            { text: "art #123", link: "https://example.com", context: "" },
        );

        const new_reference_text = "story #321";
        const updated_cross_reference = {
            new_reference_node: EditorTextNodeCreator(state).create(new_reference_text),
            position: 11,
        };

        const transaction = CrossReferenceDecorationReplacer(state).replace(
            decoration,
            updated_cross_reference,
        );
        view.dispatch(transaction);

        expect(view.dom.textContent).toBe(`Reference ${new_reference_text} and art #456`);
    });
});
