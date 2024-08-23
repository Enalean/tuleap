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

export function getPlainTextCommand(): Command {
    return function (state: EditorState, dispatch): boolean {
        const {
            tr,
            selection: { $from, $to },
            schema,
        } = state;
        if (dispatch) {
            dispatch(tr.setBlockType($from.pos, $to.pos, schema.nodes.paragraph));
        }
        return true;
    };
}

export function getHeadingCommand(level: number): Command {
    return function (state: EditorState, dispatch): boolean {
        const {
            tr,
            selection: { $from, $to },
            schema,
        } = state;
        const currentBlock = $from.parent;
        if (currentBlock.type !== schema.nodes.heading || currentBlock.attrs.level !== level) {
            if (dispatch) {
                dispatch(tr.setBlockType($from.pos, $to.pos, schema.nodes.heading, { level }));
            }
        } else {
            if (dispatch) {
                dispatch(tr.setBlockType($from.pos, $to.pos, schema.nodes.paragraph));
            }
        }
        return true;
    };
}
