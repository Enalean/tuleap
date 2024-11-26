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
import { EditorState } from "prosemirror-state";
import { DOMParser } from "prosemirror-model";
import { EditorView } from "prosemirror-view";
import { buildCustomSchema } from "../../../custom_schema";
import { createLocalDocument } from "../../../helpers";
import { AllCrossReferencesLoader } from "../first-loading/AllCrossReferencesLoader";
import { UpdatedCrossReferenceHandler } from "./UpdatedCrossReferenceHandler";
import { MarkExtentsRetriever } from "./MarkExtentsRetriever";
import { EditorNodeAtPositionFinder } from "../../../helpers/EditorNodeAtPositionFinder";

const project_id = 120;

describe("UpdatedCrossReferenceHandler", () => {
    it("Given an UpdatedCrossReference, then it should replace the target node with a new text node and a new async_cross_reference Mark", () => {
        const doc = createLocalDocument();
        const content = doc.createElement("div");

        content.insertAdjacentHTML(
            "afterbegin",
            `
            <p>This paragraph references art #123</p>
        `,
        );

        const schema = buildCustomSchema();
        const initial_state = EditorState.create({
            doc: DOMParser.fromSchema(schema).parse(content),
            schema,
        });

        const view = new EditorView(doc.createElement("div"), { state: initial_state });
        view.dispatch(AllCrossReferencesLoader(initial_state, project_id).loadAllCrossReferences());

        const updated_cross_reference = {
            position: 30, // The cross-reference extents in the text are from 27 to 35
            cross_reference_text: "art #234",
        };

        const handler = UpdatedCrossReferenceHandler(
            MarkExtentsRetriever(EditorNodeAtPositionFinder(view.state)),
            project_id,
        );

        const transaction = handler.handle(view.state, updated_cross_reference);
        if (transaction === null) {
            throw new Error("Expected a Transaction");
        }

        view.dispatch(transaction);

        expect(view.dom.innerHTML).toMatchInlineSnapshot(
            "<p>This paragraph references <async-cross-reference>art #234</async-cross-reference></p>",
        );
    });
});
