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

import { ADDED_GROUP, DELETED_GROUP, UNMOVED_GROUP } from "./types.ts";

let diff_lines;
let first_line_to_group_map;
let line_to_group_map;
let line_to_line_handles_map;
let left_lines;
let right_lines;

export function initDataAndCodeMirrors(
    file_lines,
    left_code_mirror,
    right_code_mirror,
    side_by_side_line_grouper,
    side_by_side_line_mapper
) {
    diff_lines = file_lines;
    left_lines = file_lines.filter((line) => line.old_offset !== null);
    right_lines = file_lines.filter((line) => line.new_offset !== null);

    first_line_to_group_map = side_by_side_line_grouper.buildFirstLineToGroupMap();
    line_to_group_map = side_by_side_line_grouper.buildLineToGroupMap();

    const left_content = left_lines.map(({ content }) => content).join("\n");
    const right_content = right_lines.map(({ content }) => content).join("\n");

    left_code_mirror.setValue(left_content);
    right_code_mirror.setValue(right_content);

    line_to_line_handles_map =
        side_by_side_line_mapper.buildLineToLineHandlesMap(line_to_group_map);
}

export function getGroupLines(group) {
    const begin = group.unidiff_offsets[0];
    const end = group.unidiff_offsets[group.unidiff_offsets.length - 1];
    return diff_lines.slice(begin - 1, end);
}

export function getLineHandles(line) {
    return line_to_line_handles_map.get(line);
}

export function getGroupOfLine(line) {
    return line_to_group_map.get(line.unidiff_offset);
}

export function getCommentLine(comment) {
    return diff_lines[comment.unidiff_offset - 1];
}

export function isFirstLineOfGroup(line) {
    return first_line_to_group_map.has(line.unidiff_offset);
}

export function getRightLine(line_number) {
    return right_lines[line_number];
}

export function getLeftLine(line_number) {
    return left_lines[line_number];
}

export function getLineOfHandle(handle) {
    if (typeof line_to_line_handles_map === "undefined") {
        return null;
    }
    for (const [key, value] of line_to_line_handles_map) {
        const line_group = getGroupOfLine(key);
        if (value.left_handle === handle && line_group.type === DELETED_GROUP) {
            return key;
        }
        if (value.right_handle === handle && line_group.type === ADDED_GROUP) {
            return key;
        }

        if (
            line_group.type === UNMOVED_GROUP &&
            (value.left_handle === handle || value.right_handle === handle)
        ) {
            return key;
        }
    }
    return null;
}
