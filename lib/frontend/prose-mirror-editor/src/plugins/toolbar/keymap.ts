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

import { toggleMark, wrapIn } from "prosemirror-commands";
import type { Command } from "prosemirror-state";
import type { MarkType, Schema } from "prosemirror-model";

export type ProseMirrorKeyMap = { [key: string]: Command };
export function buildKeymap(
    schema: Schema,
    map_keys?: { [key: string]: false | string },
): ProseMirrorKeyMap {
    const keys: ProseMirrorKeyMap = {};
    let type: MarkType;

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

    type = schema.marks.strong;
    bind("Mod-b", toggleMark(type));
    bind("Mod-B", toggleMark(type));

    type = schema.marks.em;
    bind("Mod-i", toggleMark(type));
    bind("Mod-I", toggleMark(type));

    type = schema.marks.code;
    bind("Mod-`", toggleMark(type));

    const node_type = schema.nodes.blockquote;
    bind("Mod->", wrapIn(node_type));
    return keys;
}
