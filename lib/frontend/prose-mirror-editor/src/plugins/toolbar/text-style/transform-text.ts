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

import type { Command, EditorState, Transaction } from "prosemirror-state";
import { custom_schema } from "../../../custom_schema";
import type { NodeType } from "prosemirror-model";

const buildSetBlockTypeCommand =
    (block_type: NodeType): Command =>
    (state: EditorState, dispatch): boolean => {
        if (!dispatch) {
            return true;
        }
        const {
            tr,
            selection: { $from, $to },
        } = state;

        dispatch(tr.setBlockType($from.pos, $to.pos, block_type));
        return true;
    };

export const getPlainTextCommand = (): Command =>
    buildSetBlockTypeCommand(custom_schema.nodes.paragraph);
export const getFormattedTextCommand = (): Command =>
    buildSetBlockTypeCommand(custom_schema.nodes.code_block);

export const getHeadingCommand =
    (level: number): Command =>
    (state: EditorState, dispatch?: (tr: Transaction) => void): boolean => {
        if (!dispatch) {
            return true;
        }

        const {
            tr,
            selection: { $from, $to },
        } = state;
        const currentBlock = $from.parent;

        if (
            currentBlock.type === custom_schema.nodes.heading &&
            currentBlock.attrs.level === level
        ) {
            dispatch(tr.setBlockType($from.pos, $to.pos, custom_schema.nodes.paragraph));
        } else {
            dispatch(tr.setBlockType($from.pos, $to.pos, custom_schema.nodes.heading, { level }));
        }

        return true;
    };
