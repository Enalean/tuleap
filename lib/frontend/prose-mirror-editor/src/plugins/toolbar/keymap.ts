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

import { chainCommands, exitCode, lift, toggleMark, wrapIn } from "prosemirror-commands";
import type { Command } from "prosemirror-state";
import { TextSelection } from "prosemirror-state";
import type { MarkType, NodeType, Schema } from "prosemirror-model";
import { liftListItem, sinkListItem, splitListItem, wrapInList } from "prosemirror-schema-list";
import { isSelectionAList } from "./list/is-list-checker";

export type ProseMirrorKeyMap = { [key: string]: Command };
export function buildKeymap(
    schema: Schema,
    map_keys?: { [key: string]: false | string },
): ProseMirrorKeyMap {
    const keys: ProseMirrorKeyMap = {};
    let mark_type: MarkType;
    let node_type: NodeType;

    function bind(key: string, cmd: Command): void {
        if (map_keys) {
            const mapped = map_keys[key];
            if (mapped === false) {
                return;
            }
            if (mapped) {
                keys[key] = cmd;
            }
        } else {
            keys[key] = cmd;
        }
    }

    mark_type = schema.marks.strong;
    bind("Mod-b", toggleMark(mark_type));
    bind("Mod-B", toggleMark(mark_type));

    mark_type = schema.marks.em;
    bind("Mod-i", toggleMark(mark_type));
    bind("Mod-I", toggleMark(mark_type));

    mark_type = schema.marks.code;
    bind("Mod-`", toggleMark(mark_type));

    const listCommand = chainCommands(exitCode, (state, dispatch) => {
        const node_type = schema.nodes.bullet_list;
        if (isSelectionAList(state, node_type)) {
            return lift(state, dispatch);
        }

        const wrapFunction = wrapInList(node_type);
        return wrapFunction(state, dispatch);
    });

    const olistCommand = chainCommands(exitCode, (state, dispatch) => {
        const node_type = schema.nodes.ordered_list;
        if (isSelectionAList(state, node_type)) {
            return lift(state, dispatch);
        }

        const wrapFunction = wrapInList(node_type);
        return wrapFunction(state, dispatch);
    });

    node_type = schema.nodes.bullet_list;
    bind("Shift-Ctrl-8", listCommand);

    node_type = schema.nodes.ordered_list;
    bind("Shift-Ctrl-9", olistCommand);

    node_type = schema.nodes.paragraph;
    const br = node_type,
        cmd = chainCommands(exitCode, (state, dispatch) => {
            if (dispatch) {
                const transaction = state.tr.replaceSelectionWith(br.create());
                transaction.setSelection(
                    TextSelection.near(transaction.doc.resolve(state.tr.selection.from + 1)),
                );
                dispatch(transaction.scrollIntoView());
            }
            return true;
        });
    bind("Mod-Enter", cmd);
    bind("Shift-Enter", cmd);

    node_type = schema.nodes.list_item;
    bind("Enter", splitListItem(node_type));
    bind("Shift-Tab", liftListItem(node_type));
    bind("Tab", sinkListItem(node_type));

    node_type = schema.nodes.blockquote;
    bind("Mod->", wrapIn(node_type));
    return keys;
}
