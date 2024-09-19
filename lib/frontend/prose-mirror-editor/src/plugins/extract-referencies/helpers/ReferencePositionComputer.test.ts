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

import { expect, describe, it } from "vitest";
import type { CrossReference } from "../reference-extractor";
import { ReferencePositionComputer } from "./ReferencePositionComputer";
import { RetrieveParentNodeStub } from "./stubs/ParentNodeRetrieverStub";
import type { EditorNode } from "../../../types/internal-types";
import { ContextLengthComputer } from "./ContextLengthPositionComputer";
import { ReferenceWithContextGetter } from "./ReferenceWithContextGetter";

describe("computePositions", () => {
    it.each([
        ["art #1", "", 0],
        ["test art #1", "test", 5],
        ["test art #1 test", "test", 5],
    ])(
        "finds position of reference for initial text '%s' with '%s art #1",
        (initial_text, context, expected_from) => {
            const start_line_position_in_prose_mirror = 0;
            const reference: CrossReference = {
                text: "art #1",
                link: "https://example.com",
                context,
            };

            const position = {
                from: expected_from,
                to: expected_from + reference.text.length + 1,
            };
            const node = {
                textContent: initial_text,
            } as EditorNode;
            const computer = ReferencePositionComputer(
                RetrieveParentNodeStub.withNode(node),
                ContextLengthComputer(),
                ReferenceWithContextGetter(),
            );
            const computed_position = computer.computesReferencePositionRelativeToNode(
                node,
                start_line_position_in_prose_mirror,
                reference,
            );

            expect(position).toStrictEqual(computed_position);
        },
    );
});
