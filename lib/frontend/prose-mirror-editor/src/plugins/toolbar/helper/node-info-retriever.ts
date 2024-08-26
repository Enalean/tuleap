/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import type { MarkType, Node, ResolvedPos } from "prosemirror-model";
import type { EditorState } from "prosemirror-state";

export interface NodeInformation {
    from: number;
    to: number;
    corresponding_node: Node;
    is_creating_node: boolean;
}

/**
 *
 * Given a cursor position, I'm returning the full context of a Node :
 * example I'm clicking on a link:
 * - from: will be the position of the first link character
 * - to: will be the last character
 * - corresponding_node: will be the Node with mark link in prose mirror
 * - is_creating_node: will tell me if I'm inserting a new node, or if I'm updating an existing one
 */
export function getWrappingNodeInfo(
    cursor: ResolvedPos,
    mark_type: MarkType,
    state: EditorState,
): NodeInformation {
    const { parent, parentOffset } = cursor;
    const start = parent.childAfter(parentOffset);
    let from = state.selection.from;
    let to = state.selection.to;
    let is_creating_node = false;
    let corresponding_node = state.doc.cut(state.selection.from, state.selection.to);

    if (!start.node) {
        is_creating_node = true;
        return { from, to, corresponding_node, is_creating_node };
    }

    const link = start.node.marks.find((mark) => mark.type.name === mark_type.name);
    if (!link) {
        is_creating_node = true;
        return { from, to, corresponding_node, is_creating_node };
    }

    from = cursor.start() + start.offset;
    to = from + start.node.nodeSize;

    corresponding_node = state.doc.cut(from, to);

    return { from, to, corresponding_node, is_creating_node };
}
