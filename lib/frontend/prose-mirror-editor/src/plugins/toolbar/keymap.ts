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
import { liftListItem, sinkListItem, splitListItem, wrapInList } from "prosemirror-schema-list";
import { custom_schema } from "../../custom_schema";
import { getHeadingCommand } from "./text-style/transform-text";
import type { DetectSingleListInSelection } from "./list/SingleListInSelectionDetector";

export type ProseMirrorKeyMap = { [key: string]: Command };
export function buildKeymap(
    detect_ordered_list: DetectSingleListInSelection,
    detect_bullet_list: DetectSingleListInSelection,
    nb_heading: number,
    map_keys?: { [key: string]: false | string },
): ProseMirrorKeyMap {
    const keys: ProseMirrorKeyMap = {};

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

    bind("Mod-b", toggleMark(custom_schema.marks.strong));
    bind("Mod-B", toggleMark(custom_schema.marks.strong));

    bind("Mod-i", toggleMark(custom_schema.marks.em));
    bind("Mod-I", toggleMark(custom_schema.marks.em));

    bind("Mod-`", toggleMark(custom_schema.marks.code));

    bind("Mod-,", toggleMark(custom_schema.marks.subscript));
    bind("Mod-.", toggleMark(custom_schema.marks.superscript));

    const listCommand = chainCommands(exitCode, (state, dispatch) => {
        const node_type = custom_schema.nodes.bullet_list;
        if (detect_bullet_list.doesSelectionContainOnlyASingleList(state.doc, state.selection)) {
            return lift(state, dispatch);
        }

        const wrapFunction = wrapInList(node_type);
        return wrapFunction(state, dispatch);
    });

    const olistCommand = chainCommands(exitCode, (state, dispatch) => {
        const node_type = custom_schema.nodes.ordered_list;
        if (detect_ordered_list.doesSelectionContainOnlyASingleList(state.doc, state.selection)) {
            return lift(state, dispatch);
        }

        const wrapFunction = wrapInList(node_type);
        return wrapFunction(state, dispatch);
    });

    bind("Shift-Ctrl-8", listCommand);

    bind("Shift-Ctrl-9", olistCommand);

    const br = custom_schema.nodes.hard_break,
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

    Array.from({ length: nb_heading }, (_, i) => i).forEach((index) => {
        bind(`Ctrl-Shift-${index + 1}`, getHeadingCommand(index + 1));
    });

    bind("Enter", splitListItem(custom_schema.nodes.list_item));
    bind("Shift-Tab", liftListItem(custom_schema.nodes.list_item));
    bind("Tab", sinkListItem(custom_schema.nodes.list_item));

    bind("Mod->", wrapIn(custom_schema.nodes.blockquote));
    return keys;
}
