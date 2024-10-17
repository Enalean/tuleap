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
import type { CheckSelectedNodesHaveSameParent } from "./SelectedNodesHaveSameParentChecker";

export type DetectPreformattedTextInSelection = {
    doesSelectionContainOnlyPreformattedText(tree: EditorNode, selection: Selection): boolean;
};

export const PreformattedTextInSelectionDetector = (
    check_same_parent: CheckSelectedNodesHaveSameParent,
): DetectPreformattedTextInSelection => ({
    doesSelectionContainOnlyPreformattedText: (tree: EditorNode, selection: Selection): boolean => {
        if (!check_same_parent.haveSelectedNodesTheSameParent(selection)) {
            return false;
        }

        let has_at_least_one_code_block = false;

        tree.nodesBetween(selection.from, selection.to, (node) => {
            if (node.type === custom_schema.nodes.code_block) {
                has_at_least_one_code_block = true;
            }
        });

        return has_at_least_one_code_block;
    },
});
