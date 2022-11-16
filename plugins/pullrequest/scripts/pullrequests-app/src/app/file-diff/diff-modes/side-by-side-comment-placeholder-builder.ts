/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import { UNMOVED_GROUP } from "./types";
import type { FileLine, GroupOfLines } from "./types";
import { isAnUnmovedLine } from "./file-line-helper";
import { doesHandleHaveWidgets, isCommentWidget } from "./side-by-side-line-widgets-helper";
import type { FileLinesState } from "./side-by-side-lines-state";
import type {
    FileLineHandle,
    LineWidgetWithNode,
    PlaceholderCreationParams,
} from "./types-codemirror-overriden";
import type { PositionPlaceholder } from "./side-by-side-placeholder-positioner";

export interface BuildCommentPlaceholder {
    buildCommentsPlaceholderWidget: (line: FileLine) => PlaceholderCreationParams | null;
}

export const SideBySideCommentPlaceholderBuilder = (
    left_code_mirror: Editor,
    right_code_mirror: Editor,
    file_lines_state: FileLinesState,
    placeholder_positioner: PositionPlaceholder
): BuildCommentPlaceholder => {
    function buildCommentsPlaceholderWidgetForGroupOfLine(
        line: FileLine
    ): PlaceholderCreationParams | null {
        const handles = file_lines_state.getLineHandles(line);
        if (!handles) {
            return null;
        }

        const left_line_of_handle = file_lines_state.getLineOfHandle(handles.left_handle);
        const right_line_of_handle = file_lines_state.getLineOfHandle(handles.right_handle);

        if (!left_line_of_handle && right_line_of_handle) {
            const right_group = file_lines_state.getGroupOfLine(right_line_of_handle);
            if (!right_group) {
                return null;
            }

            return buildCommentsPlaceholderForAddedFile(
                right_group,
                handles.left_handle,
                handles.right_handle
            );
        }

        if (!right_line_of_handle && left_line_of_handle) {
            const left_group = file_lines_state.getGroupOfLine(left_line_of_handle);
            if (!left_group) {
                return null;
            }

            return buildCommentsPlaceholderForDeletedFile(
                left_group,
                handles.left_handle,
                handles.right_handle
            );
        }

        if (!left_line_of_handle || !right_line_of_handle) {
            return null;
        }

        const left_group = file_lines_state.getGroupOfLine(left_line_of_handle);
        const right_group = file_lines_state.getGroupOfLine(right_line_of_handle);
        if (!left_group || !right_group) {
            return null;
        }

        if (isAlreadyHandled(left_group, right_group)) {
            return null;
        }

        const left_height = getGroupCommentsHeight(left_line_of_handle, handles.left_handle);
        const right_height = getGroupCommentsHeight(right_line_of_handle, handles.right_handle);

        const widget_params = buildCommentPlaceholderParams(
            handles.left_handle,
            handles.right_handle,
            left_height,
            right_height
        );

        markAsDone(left_group);
        markAsDone(right_group);
        return widget_params;
    }

    function buildCommentsPlaceholderForAddedFile(
        line_group: GroupOfLines,
        left_handle: FileLineHandle,
        right_handle: FileLineHandle
    ): PlaceholderCreationParams | null {
        const left_height = 0;
        const right_height = sumGroupCommentsHeights(line_group);

        return buildCommentPlaceholderParams(left_handle, right_handle, left_height, right_height);
    }

    function buildCommentsPlaceholderForDeletedFile(
        line_group: GroupOfLines,
        left_handle: FileLineHandle,
        right_handle: FileLineHandle
    ): PlaceholderCreationParams | null {
        const left_height = sumGroupCommentsHeights(line_group);
        const right_height = 0;

        return buildCommentPlaceholderParams(left_handle, right_handle, left_height, right_height);
    }

    function getGroupCommentsHeight(line: FileLine, handle: FileLineHandle): number {
        const line_group = file_lines_state.getGroupOfLine(line);
        if (!line_group) {
            return 0;
        }

        if (line_group.type === UNMOVED_GROUP) {
            return sumCommentsHeight(handle);
        }

        return sumGroupCommentsHeights(line_group);
    }

    function buildCommentsPlaceholderWidgetForUnmovedLine(
        line: FileLine
    ): PlaceholderCreationParams | null {
        const handles = file_lines_state.getLineHandles(line);
        if (!handles) {
            return null;
        }

        const left_height = sumCommentsHeight(handles.left_handle);
        const right_height = sumCommentsHeight(handles.right_handle);

        return buildCommentPlaceholderParams(
            handles.left_handle,
            handles.right_handle,
            left_height,
            right_height
        );
    }

    function buildCommentPlaceholderParams(
        left_handle: FileLineHandle,
        right_handle: FileLineHandle,
        left_height: number,
        right_height: number
    ): PlaceholderCreationParams | null {
        if (left_height === right_height) {
            // Nothing to do
            return null;
        }

        if (left_height > right_height) {
            const widget_height = left_height - right_height;

            const display_above_line =
                placeholder_positioner.getDisplayAboveLineForWidget(left_handle);

            return {
                code_mirror: right_code_mirror,
                handle: right_handle,
                widget_height,
                display_above_line,
                is_comment_placeholder: true,
            };
        }

        // left_height < right_height
        const widget_height = right_height - left_height;

        return {
            code_mirror: left_code_mirror,
            handle: left_handle,
            widget_height,
            display_above_line: false,
            is_comment_placeholder: true,
        };
    }

    function isAlreadyHandled(left_group: GroupOfLines, right_group: GroupOfLines): boolean {
        return isGroupDone(left_group) || isGroupDone(right_group);
    }

    function isGroupDone(group: GroupOfLines): boolean {
        return group.type !== UNMOVED_GROUP && group.has_initial_comment_placeholder;
    }

    function markAsDone(group: GroupOfLines): void {
        group.has_initial_comment_placeholder = true;
    }

    function sumGroupCommentsHeights(group: GroupOfLines): number {
        const group_lines = file_lines_state.getGroupLines(group);
        return group_lines.reduce((accumulator, line) => {
            const handle = file_lines_state.getHandleOfLine(line, group);
            return handle ? accumulator + sumCommentsHeight(handle) : accumulator;
        }, 0);
    }

    function sumCommentsHeight(handle: FileLineHandle): number {
        if (!doesHandleHaveWidgets(handle)) {
            return 0;
        }

        const comments_widgets = handle.widgets.filter((widget) => isCommentWidget(widget.node));
        if (!comments_widgets.length) {
            return 0;
        }

        return getSumOfWidgetsHeights(comments_widgets);
    }

    function getSumOfWidgetsHeights(widgets: LineWidgetWithNode[]): number {
        return widgets
            .map((widget) => widget.node.getBoundingClientRect().height)
            .reduce((sum, value) => sum + value, 0);
    }

    return {
        buildCommentsPlaceholderWidget: (line: FileLine): PlaceholderCreationParams | null => {
            if (isAnUnmovedLine(line)) {
                return buildCommentsPlaceholderWidgetForUnmovedLine(line);
            }

            return buildCommentsPlaceholderWidgetForGroupOfLine(line);
        },
    };
};
