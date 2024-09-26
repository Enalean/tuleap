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
import type { Mark } from "prosemirror-model";

type Position = {
    start: number;
    end: number;
};

function updateSelectionToWrapLinkNode(
    link_node_pos: Position,
    curr_pos_value: Position,
): Position {
    const new_pos = curr_pos_value;
    if (link_node_pos.start < curr_pos_value.start) {
        new_pos.start = link_node_pos.start;
    }
    if (link_node_pos.end > curr_pos_value.end) {
        new_pos.end = link_node_pos.end;
    }
    return new_pos;
}

export function getSelectionThatWrapsAllSelectedLinks(state: EditorState): Position {
    const { $from, $to } = state.selection;

    let selection_with_all_links: Position = {
        start: $from.pos,
        end: $to.pos,
    };

    state.doc.nodesBetween($from.pos, $to.pos, (node, pos): void => {
        node.marks.forEach((mark: Mark): void => {
            if (mark.type === state.schema.marks.link) {
                const current_node_position = {
                    start: pos,
                    end: pos + node.nodeSize,
                };
                selection_with_all_links = updateSelectionToWrapLinkNode(
                    current_node_position,
                    selection_with_all_links,
                );
            }
        });
    });

    return selection_with_all_links;
}
