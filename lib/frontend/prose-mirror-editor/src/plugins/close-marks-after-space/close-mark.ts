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

import type { MarkType } from "prosemirror-model";
import type { EditorView } from "prosemirror-view";
import type { Command } from "prosemirror-state";

export function closeMark(
    mark: MarkType,
    view: EditorView,
    toggleMark: (mark: MarkType) => Command,
): boolean {
    const { state, dispatch } = view;
    const { $from } = state.selection;

    if (mark && mark.isInSet($from.marks())) {
        const current_pos = $from.pos;
        const end_of_mark_pos = $from.end();

        if (current_pos === end_of_mark_pos) {
            return toggleMark(mark)(state, dispatch);
        }
    }
    return false;
}
