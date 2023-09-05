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

import { ADDED_GROUP, DELETED_GROUP, UNMOVED_GROUP } from "../types";
import type { FileLine, GroupOfLines } from "../types";
import type { GroupSideBySideLines } from "./SideBySideLineGrouper";
import type { MapSideBySideLines, SynchronizedLineHandles } from "./SideBySideLineMapper";
import type { PullRequestInlineCommentPresenter } from "@tuleap/plugin-pullrequest-comments";
import type { FileLineHandle } from "../types-codemirror-overriden";

let first_line_to_group_map: Map<number, GroupOfLines>;
let line_to_group_map: Map<number, GroupOfLines>;
let line_to_line_handles_map: Map<FileLine, SynchronizedLineHandles>;

export interface FileLinesState {
    getGroupLines: (group: GroupOfLines) => FileLine[];
    getLineHandles: (line: FileLine) => SynchronizedLineHandles | null;
    getLeftOrRightHandle: (line: FileLine, group: GroupOfLines) => FileLineHandle | null;
    getGroupOfLine: (line: FileLine) => GroupOfLines | null;
    getCommentLine: (comment: PullRequestInlineCommentPresenter) => FileLine | null;
    isFirstLineOfGroup: (line: FileLine) => boolean;
    getLineOfHandle: (handle: FileLineHandle) => FileLine | null;
    getOppositeHandleOfLine: (line: FileLine, group: GroupOfLines) => FileLineHandle | null;
    getHandleOfLine: (line: FileLine, group: GroupOfLines) => FileLineHandle | null;
}

export const SideBySideLineState = (
    file_lines: readonly FileLine[],
    side_by_side_line_grouper: GroupSideBySideLines,
    side_by_side_line_mapper: MapSideBySideLines,
): FileLinesState => {
    first_line_to_group_map = side_by_side_line_grouper.buildFirstLineToGroupMap();
    line_to_group_map = side_by_side_line_grouper.buildLineToGroupMap();

    line_to_line_handles_map =
        side_by_side_line_mapper.buildLineToLineHandlesMap(line_to_group_map);

    const getLineHandles = (line: FileLine): SynchronizedLineHandles | null => {
        return line_to_line_handles_map.get(line) ?? null;
    };

    const getGroupOfLine = (line: FileLine): GroupOfLines | null => {
        return line_to_group_map.get(line.unidiff_offset) ?? null;
    };

    return {
        getGroupLines: (group: GroupOfLines): FileLine[] => {
            const begin = group.unidiff_offsets[0];
            const end = group.unidiff_offsets[group.unidiff_offsets.length - 1];

            return file_lines.slice(begin - 1, end);
        },

        getLineHandles,
        getGroupOfLine,

        getLeftOrRightHandle: (line: FileLine, group: GroupOfLines): FileLineHandle | null => {
            const handles = getLineHandles(line);
            if (!handles) {
                return null;
            }
            if (group.type === ADDED_GROUP) {
                return handles.right_handle;
            }
            return handles.left_handle;
        },

        getCommentLine: (comment: PullRequestInlineCommentPresenter): FileLine | null => {
            return file_lines[comment.file.unidiff_offset - 1] ?? null;
        },

        isFirstLineOfGroup: (line: FileLine): boolean => {
            return first_line_to_group_map.has(line.unidiff_offset);
        },

        getLineOfHandle: (handle: FileLineHandle): FileLine | null => {
            for (const [key, value] of line_to_line_handles_map) {
                const line_group = getGroupOfLine(key);
                if (!line_group) {
                    continue;
                }

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
        },

        getOppositeHandleOfLine: (line: FileLine, group: GroupOfLines): FileLineHandle | null => {
            const handles = getLineHandles(line);
            if (group.type === ADDED_GROUP) {
                return handles ? handles.left_handle : null;
            }

            return handles ? handles.right_handle : null;
        },

        getHandleOfLine: (line: FileLine, group: GroupOfLines): FileLineHandle | null => {
            const handles = getLineHandles(line);
            if (group.type === ADDED_GROUP) {
                return handles ? handles.right_handle : null;
            }

            return handles ? handles.left_handle : null;
        },
    };
};
