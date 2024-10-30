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
import type { Schema } from "prosemirror-model";
import type { EditorNode } from "../../../types/internal-types";

export type DetectListsInSelection = {
    doesSelectionContainLists(tree: EditorNode, selection: Selection): boolean;
};

export const ListsInSelectionDetector = (schema: Schema): DetectListsInSelection => ({
    doesSelectionContainLists: (tree, selection): boolean => {
        const list_types = [schema.nodes.ordered_list, schema.nodes.bullet_list];
        let has_lists = false;
        tree.nodesBetween(selection.from, selection.to, (node) => {
            if (list_types.includes(node.type)) {
                has_lists = true;
            }

            return false;
        });

        return has_lists;
    },
});
