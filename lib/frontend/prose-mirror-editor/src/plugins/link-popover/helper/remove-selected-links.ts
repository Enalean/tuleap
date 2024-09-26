/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { EditorState } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { getSelectionThatWrapsAllSelectedLinks } from "./get-selection-that-wraps-all-selected-links";

export function removeSelectedLinks(state: EditorState, dispatch: EditorView["dispatch"]): void {
    const transaction = state.tr;

    const position_that_wraps_all_selected_links = getSelectionThatWrapsAllSelectedLinks(state);

    dispatch(
        transaction.removeMark(
            position_that_wraps_all_selected_links.start,
            position_that_wraps_all_selected_links.end,
            state.schema.marks.link,
        ),
    );
}
