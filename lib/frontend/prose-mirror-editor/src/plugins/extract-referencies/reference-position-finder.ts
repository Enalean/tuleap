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

import type { Node } from "prosemirror-model";
import type { CrossReference } from "./reference-extractor";

export interface ReferencePosition {
    from: number;
    to: number;
}

export function findNodesContainingReference(node: Node, reference: string): Array<number> {
    const nodes_containing_reference: Array<number> = [];
    node.descendants((child, pos) => {
        if (!child.isText) {
            return;
        }
        if (child.textContent.includes(reference)) {
            nodes_containing_reference.push(pos);
        }
    });

    return nodes_containing_reference;
}

export function computesReferencePositionRelativeToNode(
    tree: Node,
    node_position: number,
    reference: CrossReference,
): ReferencePosition {
    const line_position = tree.resolve(node_position).parent;
    const reference_position_in_line = line_position.textContent.indexOf(reference.text);
    const from = node_position + reference_position_in_line;
    const to = from + reference.text.length + 1;

    return {
        from,
        to,
    };
}
