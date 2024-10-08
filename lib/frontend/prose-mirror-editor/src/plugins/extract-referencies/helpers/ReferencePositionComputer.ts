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

import type { CrossReference } from "../reference-extractor";
import type { Node } from "prosemirror-model";
import type { RetrieveParentNode } from "./ParentNodeRetriever";
import type { ComputeContextLength } from "./ContextLengthPositionComputer";
import type { GetReferenceWithContext } from "./ReferenceWithContextGetter";

export interface ReferencePosition {
    from: number;
    to: number;
}

export type ComputeReferencePosition = {
    computesReferencePositionRelativeToNode(
        tree: Node,
        node_position: number,
        reference: CrossReference,
    ): ReferencePosition;
};

export const ReferencePositionComputer = (
    parent_node_retriever: RetrieveParentNode,
    context_length_computer: ComputeContextLength,
    reference_with_context_getter: GetReferenceWithContext,
): ComputeReferencePosition => ({
    computesReferencePositionRelativeToNode: (
        tree: Node,
        node_position: number,
        reference: CrossReference,
    ): ReferencePosition => {
        const line_position = parent_node_retriever.retrieveParentNode(tree, node_position);

        const reference_with_context =
            reference_with_context_getter.getReferenceWithContext(reference);
        const context_length = context_length_computer.computeContextLength(reference);

        const reference_position_in_line =
            line_position.textContent.indexOf(reference_with_context);
        const from = node_position + reference_position_in_line + context_length;
        const to = from + reference.text.length + 1;

        return {
            from,
            to,
        };
    },
});
