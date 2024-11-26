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

import { describe, it, expect, beforeEach } from "vitest";
import type { Schema } from "prosemirror-model";
import { FindEditorNodeAtPositionStub } from "../../../helpers/stubs/FindEditorNodeAtPositionStub";
import { buildCustomSchema } from "../../../custom_schema";
import { MarkExtentsRetriever } from "./MarkExtentsRetriever";
import type { RetrieveMarkExtents } from "./MarkExtentsRetriever";

describe("MarkExtentsRetriever", () => {
    let schema: Schema, retriever: RetrieveMarkExtents;

    beforeEach(() => {
        schema = buildCustomSchema();

        const this_is = schema.text("This is");
        const bold = schema.text("bold").mark([schema.marks.strong.create()]);
        const dot = schema.text(".");

        const find_node_at_position = FindEditorNodeAtPositionStub.withNodes(
            new Map([
                [4, this_is],
                [5, this_is],
                [6, this_is],
                [7, this_is],
                [8, this_is],
                [9, this_is],
                [10, this_is],
                [11, this_is],
                [12, bold],
                [13, bold],
                [14, bold],
                [15, bold],
                [16, dot],
            ]),
        );

        retriever = MarkExtentsRetriever(find_node_at_position);
    });

    it("Given a position, When it points on an EditorNode having the given Mark in its set, then it should return the extends of the Mark", () => {
        const bold_text_positions = [12, 13, 14, 15];

        bold_text_positions.forEach((position) => {
            const extents = retriever.retrieveExtentsOfMarkAtPosition(
                schema.marks.strong,
                position,
            );

            expect(extents).toStrictEqual({
                from: 12,
                to: 15,
            });
        });
    });

    it("Given a position, When the target mark type is not found, then it should return null", () => {
        const extents = retriever.retrieveExtentsOfMarkAtPosition(schema.marks.em, 4);

        expect(extents).toBe(null);
    });
});
