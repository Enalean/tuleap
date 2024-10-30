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
import type { Heading } from "./Heading";
import type { CheckSelectedNodesHaveSameParent } from "./SelectedNodesHaveSameParentChecker";

export type RetrieveHeading = {
    retrieveHeadingInSelection(tree: EditorNode, selection: Selection): Heading | null;
};

export const HeadingInSelectionRetriever = (
    check_same_parent: CheckSelectedNodesHaveSameParent,
    heading_node_type: NodeType,
): RetrieveHeading => {
    const retrieveHeadingAtCursorPosition = (selection: Selection): Heading | null => {
        const node_at_cursor_position = selection.$head.node();

        return node_at_cursor_position.type === heading_node_type
            ? { level: node_at_cursor_position.attrs.level }
            : null;
    };

    const retrieveHeadingInSelection = (tree: EditorNode, selection: Selection): Heading | null => {
        let found_heading: EditorNode | undefined;

        tree.nodesBetween(selection.from, selection.to, (node) => {
            if (node.type === heading_node_type) {
                found_heading = node;
            }
        });

        if (!found_heading) {
            return null;
        }

        return { level: found_heading.attrs.level };
    };

    return {
        retrieveHeadingInSelection: (tree: EditorNode, selection: Selection): Heading | null => {
            if (!check_same_parent.haveSelectedNodesTheSameParent(selection)) {
                return null;
            }

            return selection.empty
                ? retrieveHeadingAtCursorPosition(selection)
                : retrieveHeadingInSelection(tree, selection);
        },
    };
};
