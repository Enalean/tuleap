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
import type { FileLineHandle } from "../types-codemirror-overriden";
import { ADDED_GROUP, DELETED_GROUP } from "../types";
import type { FileLine, GroupOfLines } from "../types";
import { isAnAddedLine, isAnUnmovedLine, isARemovedLine } from "./file-line-helper";

export interface SynchronizedLineHandles {
    left_handle: FileLineHandle;
    right_handle: FileLineHandle;
}

export interface MapSideBySideLines {
    buildLineToLineHandlesMap: (
        line_to_group_map: Map<number, GroupOfLines>,
    ) => Map<FileLine, SynchronizedLineHandles>;
}

export const SideBySideLineMapper = (
    lines: readonly FileLine[],
    left_code_mirror: Editor,
    right_code_mirror: Editor,
): MapSideBySideLines => ({
    buildLineToLineHandlesMap(
        line_to_group_map: Map<number, GroupOfLines>,
    ): Map<FileLine, SynchronizedLineHandles> {
        function getDeletedGroupOppositeLineIndex(group: GroupOfLines): number {
            const group_last_line_unidiff_offset =
                group.unidiff_offsets[group.unidiff_offsets.length - 1];
            const group_next_line = lines[group_last_line_unidiff_offset];
            if (group_next_line && !isARemovedLine(group_next_line)) {
                return group_next_line.new_offset - 1;
            }
            const group_first_line_unidiff_offset = group.unidiff_offsets[0];
            const group_previous_line = lines[group_first_line_unidiff_offset - 2];
            if (group_previous_line && !isARemovedLine(group_previous_line)) {
                return group_previous_line.new_offset - 1;
            }
            return 0;
        }

        function getPreviousGroupLineIndex(group: GroupOfLines): number {
            const group_first_line_unidiff_offset = group.unidiff_offsets[0];
            const group_previous_line = lines[group_first_line_unidiff_offset - 2];
            if (group_previous_line && !isAnAddedLine(group_previous_line)) {
                return group_previous_line.old_offset - 1;
            }
            return 0;
        }

        return lines.reduce((accumulator, line: FileLine) => {
            if (isAnUnmovedLine(line)) {
                const left_handle = left_code_mirror.getLineHandle(line.old_offset - 1);
                const right_handle = right_code_mirror.getLineHandle(line.new_offset - 1);
                accumulator.set(line, { left_handle, right_handle });
                return accumulator;
            }

            const group = line_to_group_map.get(line.unidiff_offset);
            if (group && group.type === DELETED_GROUP && isARemovedLine(line)) {
                const placeholder_line_index = getDeletedGroupOppositeLineIndex(group);
                const left_handle = left_code_mirror.getLineHandle(line.old_offset - 1);
                const right_handle = right_code_mirror.getLineHandle(placeholder_line_index);
                accumulator.set(line, { left_handle, right_handle });
                return accumulator;
            }
            if (group && group.type === ADDED_GROUP && isAnAddedLine(line)) {
                const placeholder_line_index = getPreviousGroupLineIndex(group);
                const left_handle = left_code_mirror.getLineHandle(placeholder_line_index);
                const right_handle = right_code_mirror.getLineHandle(line.new_offset - 1);
                accumulator.set(line, { left_handle, right_handle });
                return accumulator;
            }
            return accumulator;
        }, new Map<FileLine, SynchronizedLineHandles>());
    },
});
