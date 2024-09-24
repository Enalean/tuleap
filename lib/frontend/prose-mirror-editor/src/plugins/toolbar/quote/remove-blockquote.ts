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
import { liftTarget } from "prosemirror-transform";

export function removeBlockQuote(state: EditorState, dispatch?: EditorView["dispatch"]): void {
    const range = state.selection.$from.blockRange(
        state.selection.$to,
        (node) => node.type === state.schema.nodes.blockquote,
    );
    if (!range) {
        return;
    }

    const blockquote_depth = liftTarget(range);
    if (blockquote_depth === null) {
        return;
    }

    if (dispatch) {
        dispatch(state.tr.lift(range, blockquote_depth));
    }
}
