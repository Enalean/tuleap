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
import { getWordOrUrlJustBeforeCursor } from "../../helpers/get-word-or-url-just-before-cursor";
import { getLinkUrlFromText } from "./get-link-url-from-text";

function createLink(
    state: EditorState,
    dispatch: EditorView["dispatch"],
    href: string,
    from: number,
    to: number,
): void {
    dispatch(state.tr.addMark(from, to, state.schema.marks.link.create({ href })));
}

export function convertUrlToLinkAfterASpace(
    state: EditorState,
    dispatch: EditorView["dispatch"],
    from: number,
    text: string,
): boolean {
    if (text === " ") {
        const word_or_url_just_before_cursor = getWordOrUrlJustBeforeCursor(state);
        const url: string | undefined = getLinkUrlFromText(word_or_url_just_before_cursor);
        if (url) {
            const cursor_position = from;
            const url_position = cursor_position - url.length;
            createLink(state, dispatch, url, url_position, cursor_position);
        }
    }

    return false;
}
