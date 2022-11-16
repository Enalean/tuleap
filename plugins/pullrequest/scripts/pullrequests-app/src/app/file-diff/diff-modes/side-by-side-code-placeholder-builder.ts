/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 */

import type { Editor } from "codemirror";
import type {
    FileLineHandle,
    LineHandleWithAHeight,
    PlaceholderCreationParams,
} from "./types-codemirror-overriden";
import type { FileLinesState } from "./side-by-side-lines-state";
import type { FileLine, GroupOfLines } from "./types";
import { ADDED_GROUP, DELETED_GROUP } from "./types";

interface BuildCodePlaceholder {
    buildCodePlaceholderWidget: (line: FileLine) => PlaceholderCreationParams | null;
}

export const SideBySideCodePlaceholderBuilder = (
    left_code_mirror: Editor,
    right_code_mirror: Editor,
    file_lines_state: FileLinesState
): BuildCodePlaceholder => {
    const getCodeMirror = (group: GroupOfLines): Editor => {
        return group.type === ADDED_GROUP ? left_code_mirror : right_code_mirror;
    };

    return {
        buildCodePlaceholderWidget: (line: FileLine): PlaceholderCreationParams | null => {
            const line_group = file_lines_state.getGroupOfLine(line);
            if (!line_group) {
                return null;
            }

            const handle = file_lines_state.getOppositeHandleOfLine(line, line_group);
            if (!handle) {
                return null;
            }

            const group_height = sumGroupLinesHeight(file_lines_state, line_group);

            return {
                code_mirror: getCodeMirror(line_group),
                handle,
                widget_height: getAdjustedPlaceholderWidgetAndHeight(line, handle, group_height),
                display_above_line: line_group.type === DELETED_GROUP,
                is_comment_placeholder: false,
            };
        },
    };
};

function sumGroupLinesHeight(file_lines_state: FileLinesState, group: GroupOfLines): number {
    const group_lines = file_lines_state.getGroupLines(group);
    return group_lines.reduce((accumulator, line) => {
        const handle = file_lines_state.getHandleOfLine(line, group);
        return isAFileLineHandleWithAHeight(handle) ? accumulator + handle.height : accumulator;
    }, 0);
}

function getAdjustedPlaceholderWidgetAndHeight(
    line: FileLine,
    placeholder_line_handle: FileLineHandle,
    widget_height: number
): number {
    if (
        isAFileLineHandleWithAHeight(placeholder_line_handle) &&
        (line.new_offset === 1 || line.old_offset === 1)
    ) {
        return widget_height - placeholder_line_handle.height;
    }

    return widget_height;
}

function isAFileLineHandleWithAHeight(
    handle: FileLineHandle | null
): handle is LineHandleWithAHeight {
    return handle !== null && "height" in handle;
}
