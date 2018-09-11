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

import { buildLineGroups } from "./side-by-side-line-grouper.js";
import { buildLineToLineHandlesMap } from "./side-by-side-line-mapper.js";

let diff_lines;
let first_line_to_group_map;
let line_to_group_map;
let line_to_line_handles_map;

function initDataAndCodeMirrors(file_lines, left_code_mirror, right_code_mirror) {
    diff_lines = file_lines;
    const left_lines = file_lines.filter(line => line.old_offset !== null);
    const right_lines = file_lines.filter(line => line.new_offset !== null);

    const maps = buildLineGroups(diff_lines);
    first_line_to_group_map = maps.first_line_to_group_map;
    line_to_group_map = maps.line_to_group_map;

    const left_content = left_lines.map(({ content }) => content).join("\n");
    const right_content = right_lines.map(({ content }) => content).join("\n");

    left_code_mirror.setValue(left_content);
    right_code_mirror.setValue(right_content);

    line_to_line_handles_map = buildLineToLineHandlesMap(
        diff_lines,
        line_to_group_map,
        left_code_mirror,
        right_code_mirror
    );
}

function getGroupLines(group) {
    const begin = group.unidiff_offsets[0];
    const end = group.unidiff_offsets[group.unidiff_offsets.length - 1];
    return diff_lines.slice(begin - 1, end);
}

function getLineHandles(line) {
    return line_to_line_handles_map.get(line);
}

function getGroupOfLine(line) {
    return line_to_group_map.get(line.unidiff_offset);
}

function getCommentLine(comment) {
    return diff_lines[comment.unidiff_offset - 1];
}

function isFirstLineOfGroup(line) {
    return first_line_to_group_map.has(line.unidiff_offset);
}

function hasNextLine(line) {
    return line.unidiff_offset < diff_lines.length;
}

function getNextLine(line) {
    return diff_lines[line.unidiff_offset];
}

export {
    initDataAndCodeMirrors,
    isFirstLineOfGroup,
    getGroupLines,
    getLineHandles,
    getCommentLine,
    getGroupOfLine,
    hasNextLine,
    getNextLine
};
