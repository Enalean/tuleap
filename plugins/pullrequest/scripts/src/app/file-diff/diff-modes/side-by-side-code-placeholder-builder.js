/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { ADDED_GROUP, DELETED_GROUP } from "./side-by-side-line-grouper.js";
import {
    getGroupLines,
    getGroupOfLine,
    getLineHandles,
    getNextLine,
    hasNextLine,
} from "./side-by-side-lines-state.js";

export { buildCodePlaceholderWidget };

function buildCodePlaceholderWidget(line, left_code_mirror, right_code_mirror) {
    const line_group = getGroupOfLine(line);
    const handle = getOppositeHandle(line, line_group);
    const code_mirror = getCodeMirror(line_group, left_code_mirror, right_code_mirror);
    const group_height = sumGroupLinesHeight(line_group);
    const widget_height = adjustWidgetPlacementAndHeight(line, handle, group_height);
    let display_above_line = false;
    if (line_group.type === DELETED_GROUP) {
        display_above_line = true;
    }
    return {
        code_mirror,
        handle,
        widget_height,
        display_above_line,
        is_comment_placeholder: false,
    };
}

function getCodeMirror(group, left_code_mirror, right_code_mirror) {
    return group.type === ADDED_GROUP ? left_code_mirror : right_code_mirror;
}

function getOppositeHandle(line, group) {
    if (group.type === ADDED_GROUP) {
        const { left_handle } = getLineHandles(line);
        return left_handle;
    }

    const { right_handle } = getLineHandles(line);
    return right_handle;
}

function sumGroupLinesHeight(group) {
    const group_lines = getGroupLines(group);
    return group_lines.reduce((accumulator, line) => {
        const handle = getHandle(line, group);
        return accumulator + handle.height;
    }, 0);
}

function adjustWidgetPlacementAndHeight(line, placeholder_line_handle, widget_height) {
    if (isFirstLineModified(line)) {
        return widget_height;
    }

    if (line.new_offset === 1 || line.old_offset === 1) {
        return widget_height - placeholder_line_handle.height;
    }

    return widget_height;
}

function isFirstLineModified(line) {
    if (line.unidiff_offset > 1 || !hasNextLine(line)) {
        return false;
    }

    const next_line = getNextLine(line);
    const next_line_group = getGroupOfLine(next_line);
    const line_group = getGroupOfLine(line);

    return line_group.type === DELETED_GROUP && next_line_group.type === ADDED_GROUP;
}

function getHandle(line, group) {
    if (group.type === ADDED_GROUP) {
        const { right_handle } = getLineHandles(line);
        return right_handle;
    }

    const { left_handle } = getLineHandles(line);
    return left_handle;
}
