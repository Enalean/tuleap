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

function buildLineToLineHandlesMap(lines, line_to_group_map, left_code_mirror, right_code_mirror) {
    return lines.reduce((accumulator, line, index, array) => {
        if (lineIsUnmoved(line)) {
            const left_handle = left_code_mirror.getLineHandle(line.old_offset - 1);
            const right_handle = right_code_mirror.getLineHandle(line.new_offset - 1);
            accumulator.set(line, { left_handle, right_handle });
            return accumulator;
        }

        const group = line_to_group_map.get(line.unidiff_offset);
        const group_first_line_unidiff_offset = group.unidiff_offsets[0];
        const group_last_line_unidiff_offset =
            group.unidiff_offsets[group.unidiff_offsets.length - 1];
        if (group.type === DELETED_GROUP) {
            const group_next_line = array[group_last_line_unidiff_offset];
            const placeholder_line_number = group_next_line ? group_next_line.new_offset - 1 : 0;
            const left_handle = left_code_mirror.getLineHandle(line.old_offset - 1);
            const right_handle = right_code_mirror.getLineHandle(placeholder_line_number);
            accumulator.set(line, { left_handle, right_handle });
            return accumulator;
        }
        if (group.type === ADDED_GROUP) {
            const group_previous_line = array[group_first_line_unidiff_offset - 2];
            const placeholder_line_number = group_previous_line
                ? group_previous_line.old_offset - 1
                : 0;
            const left_handle = left_code_mirror.getLineHandle(placeholder_line_number);
            const right_handle = right_code_mirror.getLineHandle(line.new_offset - 1);
            accumulator.set(line, { left_handle, right_handle });
            return accumulator;
        }
        return accumulator;
    }, new Map());
}

function lineIsUnmoved(line) {
    return line.new_offset !== null && line.old_offset !== null;
}

export { buildLineToLineHandlesMap };
