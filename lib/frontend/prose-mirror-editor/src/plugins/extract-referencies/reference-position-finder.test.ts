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
import { createLocalDocument } from "../../helpers/helper-for-test";
import { EditorState } from "prosemirror-state";
import { DOMParser } from "prosemirror-model";
import { custom_schema } from "../../custom_schema";
import {
    findNodesContainingReference,
    computesReferencePositionRelativeToNode,
} from "./reference-position-finder";

describe("reference position finder", () => {
    it.each<[string, Array<number>, Array<number>]>([
        ["art #1", [1], [1]],
        ["test art #1", [1], [6]],
        ["test art #1 test", [1], [6]],
        ["<p><ul><li>test</li><li>art #1</li></ul></p>", [13], [13]],
        ["<p><ul><li>test with art #1</li><li>art #1</li></ul></p>", [5, 25], [15, 25]],
    ])(
        "finds position of node (%s) and position of reference",
        (
            initial_text: string,
            expected_positions: Array<number>,
            expected_reference_position: Array<number>,
        ) => {
            const reference = {
                text: "art #1",
                link: "https://example.com",
            };
            const local_document: Document = createLocalDocument();
            const editor_content = local_document.createElement("div");
            editor_content.innerHTML = initial_text;
            const state = EditorState.create({
                doc: DOMParser.fromSchema(custom_schema).parse(editor_content),
                schema: custom_schema,
            });

            const positions = findNodesContainingReference(state.doc, "art #1");
            expect(expected_positions).toEqual(positions);

            positions.forEach((position, key) => {
                const reference_position = computesReferencePositionRelativeToNode(
                    state.doc,
                    position,
                    reference,
                );

                expect(expected_reference_position[key]).toBe(reference_position.from);
            });
        },
    );
});
