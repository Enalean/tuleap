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

import type { Selection } from "prosemirror-state";
import type { EditorNode } from "../../../types/internal-types";
import { custom_schema } from "../../../custom_schema";

export type DetectHeadingsInSelection = {
    doesSelectionContainHeadings(tree: EditorNode, selection: Selection): boolean;
};

export const HeadingsInSelectionDetector = (): DetectHeadingsInSelection => ({
    doesSelectionContainHeadings: (tree, selection): boolean => {
        let has_found_a_heading = false;

        tree.nodesBetween(selection.from, selection.to, (node) => {
            if (node.type === custom_schema.nodes.heading) {
                has_found_a_heading = true;
            }
        });

        return has_found_a_heading;
    },
});
