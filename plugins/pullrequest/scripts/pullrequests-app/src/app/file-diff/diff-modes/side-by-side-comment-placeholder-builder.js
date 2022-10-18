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

import { ADDED_GROUP, UNMOVED_GROUP } from "./side-by-side-line-grouper.js";
import {
    getGroupLines,
    getGroupOfLine,
    getLineHandles,
    getLineOfHandle,
} from "./side-by-side-lines-state.js";
import { NAME as INLINE_COMMENT_NAME } from "../inline-comment-component.js";
import { NAME as NEW_INLINE_COMMENT_NAME } from "../new-inline-comment-component.js";

import { getDisplayAboveLineForWidget } from "./side-by-side-placeholder-positioner.js";

export { buildCommentsPlaceholderWidget };

function buildCommentsPlaceholderWidget(line, left_code_mirror, right_code_mirror) {
    if (lineIsUnmoved(line)) {
        return buildCommentsPlaceholderWidgetForUnmovedLine(
            line,
            left_code_mirror,
            right_code_mirror
        );
    }

    return buildCommentsPlaceholderWidgetForGroupOfLine(line, left_code_mirror, right_code_mirror);
}

function buildCommentsPlaceholderWidgetForGroupOfLine(line, left_code_mirror, right_code_mirror) {
    const { left_handle, right_handle } = getLineHandles(line);
    const left_line_of_handle = getLineOfHandle(left_handle);
    const right_line_of_handle = getLineOfHandle(right_handle);

    if (!left_line_of_handle) {
        const right_group = getGroupOfLine(right_line_of_handle);
        return buildCommentsPlaceholderForAddedFile(
            right_group,
            left_handle,
            right_handle,
            left_code_mirror,
            right_code_mirror
        );
    }

    if (!right_line_of_handle) {
        const left_group = getGroupOfLine(left_line_of_handle);
        return buildCommentsPlaceholderForDeletedFile(
            left_group,
            left_handle,
            right_handle,
            left_code_mirror,
            right_code_mirror
        );
    }

    const left_group = getGroupOfLine(left_line_of_handle);
    const right_group = getGroupOfLine(right_line_of_handle);
    if (isAlreadyHandled(left_group, right_group)) {
        return null;
    }

    const left_height = getGroupCommentsHeight(left_line_of_handle, left_handle);
    const right_height = getGroupCommentsHeight(right_line_of_handle, right_handle);

    const widget_params = buildCommentPlaceholderParams(
        left_code_mirror,
        right_code_mirror,
        left_handle,
        right_handle,
        left_height,
        right_height
    );

    markAsDone(left_group);
    markAsDone(right_group);
    return widget_params;
}

function buildCommentsPlaceholderForAddedFile(
    line_group,
    left_handle,
    right_handle,
    left_code_mirror,
    right_code_mirror
) {
    const left_height = 0;
    const right_height = sumGroupCommentsHeights(line_group);

    return buildCommentPlaceholderParams(
        left_code_mirror,
        right_code_mirror,
        left_handle,
        right_handle,
        left_height,
        right_height
    );
}

function buildCommentsPlaceholderForDeletedFile(
    line_group,
    left_handle,
    right_handle,
    left_code_mirror,
    right_code_mirror
) {
    const left_height = sumGroupCommentsHeights(line_group);
    const right_height = 0;

    return buildCommentPlaceholderParams(
        left_code_mirror,
        right_code_mirror,
        left_handle,
        right_handle,
        left_height,
        right_height
    );
}

function getGroupCommentsHeight(line, handle) {
    const line_group = getGroupOfLine(line);

    if (line_group.type === UNMOVED_GROUP) {
        return sumCommentsHeight(handle);
    }

    return sumGroupCommentsHeights(line_group);
}

function buildCommentsPlaceholderWidgetForUnmovedLine(line, left_code_mirror, right_code_mirror) {
    const { left_handle, right_handle } = getLineHandles(line);

    const left_height = sumCommentsHeight(left_handle);
    const right_height = sumCommentsHeight(right_handle);

    return buildCommentPlaceholderParams(
        left_code_mirror,
        right_code_mirror,
        left_handle,
        right_handle,
        left_height,
        right_height
    );
}

function buildCommentPlaceholderParams(
    left_code_mirror,
    right_code_mirror,
    left_handle,
    right_handle,
    left_height,
    right_height
) {
    if (left_height === right_height) {
        // Nothing to do
        return null;
    }

    if (left_height > right_height) {
        const widget_height = left_height - right_height;

        const display_above_line = getDisplayAboveLineForWidget(left_handle);

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

function isAlreadyHandled(left_group, right_group) {
    return isGroupDone(left_group) || isGroupDone(right_group);
}

function isGroupDone(group) {
    return group.type !== UNMOVED_GROUP && group.has_initial_comment_placeholder;
}

function markAsDone(group) {
    group.has_initial_comment_placeholder = true;
}

function getHandle(line, group) {
    if (group.type === ADDED_GROUP) {
        const { right_handle } = getLineHandles(line);
        return right_handle;
    }

    const { left_handle } = getLineHandles(line);
    return left_handle;
}

function sumGroupCommentsHeights(group) {
    const group_lines = getGroupLines(group);
    return group_lines.reduce((accumulator, line) => {
        const handle = getHandle(line, group);
        return accumulator + sumCommentsHeight(handle);
    }, 0);
}

function sumCommentsHeight(handle) {
    if (hasNoWidgets(handle)) {
        return 0;
    }

    const comments_widgets = handle.widgets.filter((widget) => isCommentWidget(widget));

    if (!comments_widgets.length) {
        return 0;
    }

    return getSumOfWidgetsHeights(comments_widgets);
}

function isCommentWidget(line_widget) {
    return (
        line_widget.node.localName === NEW_INLINE_COMMENT_NAME ||
        line_widget.node.localName === INLINE_COMMENT_NAME
    );
}

function getSumOfWidgetsHeights(widgets) {
    return widgets.map((widget) => widget.height).reduce((sum, value) => sum + value, 0);
}

function hasNoWidgets(handle) {
    return (
        !Object.prototype.hasOwnProperty.call(handle, "widgets") ||
        !handle.widgets ||
        (Object.prototype.hasOwnProperty.call(handle, "widgets") && handle.widgets.length === 0)
    );
}

function lineIsUnmoved(line) {
    return line.new_offset !== null && line.old_offset !== null;
}
