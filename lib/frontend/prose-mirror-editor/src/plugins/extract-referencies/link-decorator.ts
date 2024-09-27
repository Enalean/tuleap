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
import type { Decoration } from "prosemirror-view";
import { DecorationSet } from "prosemirror-view";
import {
    computesReferencePositionRelativeToNode,
    findNodesContainingReference,
} from "./reference-position-finder";
import { createCrossReferenceDecoration } from "../../helpers/create-cross-reference-decoration";

export function decorateLink(tree: Node, references: Array<CrossReference>): DecorationSet {
    const decorated_links: Array<Decoration> = [];
    references.forEach((reference) => {
        const line_positions = findNodesContainingReference(tree, reference.text);

        const decorations = line_positions.map((node_position) => {
            const reference_position = computesReferencePositionRelativeToNode(
                tree,
                node_position,
                reference,
            );
            return createCrossReferenceDecoration(reference_position, reference);
        });
        decorated_links.push(...decorations);
    });

    return DecorationSet.create(tree, decorated_links);
}
