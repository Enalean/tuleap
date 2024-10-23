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
import type { DetectSingleListInSelection } from "./SingleListInSelectionDetector";
import type { DetectListsInSelection } from "./ListsInSelectionDetector";
import { ListState } from "./ListState";

export type BuildListState = {
    build(): ListState;
};

export const ListStateBuilder = (
    state: EditorState,
    detect_target_list: DetectSingleListInSelection,
    detect_multiple_lists: DetectListsInSelection,
): BuildListState => ({
    build: (): ListState => {
        if (detect_target_list.doesSelectionContainOnlyASingleList(state.doc, state.selection)) {
            return ListState.activated();
        }

        if (detect_multiple_lists.doesSelectionContainLists(state.doc, state.selection)) {
            return ListState.disabled();
        }

        return ListState.enabled();
    },
});
