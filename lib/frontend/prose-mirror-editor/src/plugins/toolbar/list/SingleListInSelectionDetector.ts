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
import type { NodeType } from "prosemirror-model";
import type { EditorNode } from "../../../types/internal-types";
import { isNodeAStructureBlock } from "../../../helpers/isNodeAStructureBlock";

export type DetectSingleListInSelection = {
    doesSelectionContainOnlyASingleList(tree: EditorNode, selection: Selection): boolean;
};

export const SingleListInSelectionDetector = (
    list_type: NodeType,
): DetectSingleListInSelection => ({
    doesSelectionContainOnlyASingleList: (tree, selection): boolean => {
        let nb_lists_found = 0;
        let has_other_nodes_than_list = false;

        tree.nodesBetween(selection.from, selection.to, (node) => {
            if (isNodeAStructureBlock(node)) {
                return true;
            }

            if (node.type === list_type) {
                nb_lists_found++;
            } else {
                has_other_nodes_than_list = true;
            }

            return false;
        });

        return nb_lists_found === 1 && !has_other_nodes_than_list;
    },
});
