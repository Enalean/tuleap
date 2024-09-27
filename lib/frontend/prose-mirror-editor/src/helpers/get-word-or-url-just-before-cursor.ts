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

export function getWordOrUrlJustBeforeCursor(state: EditorState): string {
    const { $from } = state.selection;
    const node_start = $from.start();
    const cursor_pos = $from.pos;
    const text_from_node_start_to_cursor = state.doc.textBetween(node_start, cursor_pos);
    const text_split_by_space = text_from_node_start_to_cursor.split(" ");
    return text_split_by_space.length > 0
        ? text_split_by_space[text_split_by_space.length - 1]
        : "";
}
