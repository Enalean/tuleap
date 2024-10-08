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

import type { EditorState } from "prosemirror-state";
import { ListState } from "./ListState";
import type { NodeType } from "prosemirror-model";
import type { CheckIsSelectionAListWithType } from "./IsSelectionAListWithTypeChecker";

export type BuildListState = {
    build(activated_node_type: NodeType, forbidden_node_type: NodeType): ListState;
};

export const ListStateBuilder = (
    state: EditorState,
    list_type_checker: CheckIsSelectionAListWithType,
): BuildListState => ({
    build: (activated_node_type: NodeType, forbidden_node_type: NodeType): ListState => {
        if (list_type_checker.isSelectionAListWithType(state, activated_node_type)) {
            return ListState.activated();
        }
        if (list_type_checker.isSelectionAListWithType(state, forbidden_node_type)) {
            return ListState.disabled();
        }

        return ListState.enabled();
    },
});
