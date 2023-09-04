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
import type { FileLineHandle, LineHandleWithAHeight } from "../../types-codemirror-overriden";
import type { FileLinesState } from "../../file-lines/SideBySideLineState";
import type { FileLine, GroupOfLines } from "../../types";
import type { ManageCodeMirrorsContent } from "../../editors/SideBySideCodeMirrorsContentManager";
import type { CreatePlaceholderWidget } from "../SideBySideCodeMirrorWidgetCreator";
import { ADDED_GROUP, DELETED_GROUP } from "../../types";
import { isAnUnmovedLine } from "../../file-lines/file-line-helper";

interface ManageCodePlaceholdersCreation {
    displayCodePlaceholderIfNeeded: (line: FileLine) => void;
}

export const SideBySideCodePlaceholderCreationManager = (
    code_mirrors_content_manager: ManageCodeMirrorsContent,
    file_lines_state: FileLinesState,
    placeholder_widget_creator: CreatePlaceholderWidget,
): ManageCodePlaceholdersCreation => {
    const getCodeMirror = (group: GroupOfLines): Editor => {
        return group.type === ADDED_GROUP
            ? code_mirrors_content_manager.getLeftCodeMirrorEditor()
            : code_mirrors_content_manager.getRightCodeMirrorEditor();
    };

    return {
        displayCodePlaceholderIfNeeded: (line: FileLine): void => {
            if (isAnUnmovedLine(line) || !file_lines_state.isFirstLineOfGroup(line)) {
                return;
            }

            const line_group = file_lines_state.getGroupOfLine(line);
            if (!line_group) {
                return;
            }

            const handle = file_lines_state.getOppositeHandleOfLine(line, line_group);
            if (!handle) {
                return;
            }

            const group_height = sumGroupLinesHeight(file_lines_state, line_group);

            placeholder_widget_creator.displayPlaceholderWidget({
                code_mirror: getCodeMirror(line_group),
                handle,
                widget_height: getAdjustedPlaceholderWidgetAndHeight(line, handle, group_height),
                display_above_line: isFirstLineOfFile(line) || line_group.type === DELETED_GROUP,
                is_comment_placeholder: false,
            });
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
    widget_height: number,
): number {
    if (isFirstLineOfFile(line) && isAFileLineHandleWithAHeight(placeholder_line_handle)) {
        return Math.abs(widget_height - placeholder_line_handle.height);
    }

    return widget_height;
}

function isFirstLineOfFile(line: FileLine): boolean {
    return line.new_offset === 1 || line.old_offset === 1;
}

function isAFileLineHandleWithAHeight(
    handle: FileLineHandle | null,
): handle is LineHandleWithAHeight {
    return handle !== null && "height" in handle;
}
