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

import type { Command, EditorState } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { removeBlockQuote } from "./remove-blockquote";
import { hasPreviousNodeType } from "./has-previous-node-type";
import { addBlockQuote } from "./add-blockquote";

export function toggleBlockQuote(
    state: EditorState,
    dispatch?: EditorView["dispatch"] | undefined,
): void {
    if (hasPreviousNodeType(state, state.schema.nodes.blockquote)) {
        removeBlockQuote(state, dispatch);
    } else {
        addBlockQuote(state, dispatch);
    }
}

export function getQuoteCommand(): Command {
    return (state: EditorState, dispatch?: EditorView["dispatch"] | undefined): boolean => {
        if (dispatch) {
            toggleBlockQuote(state, dispatch);
        }

        return true;
    };
}
