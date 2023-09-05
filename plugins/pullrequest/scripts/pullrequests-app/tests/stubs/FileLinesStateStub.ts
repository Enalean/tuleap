/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { GroupSideBySideLinesStub } from "./GroupSideBySideLinesStub";
import { MapSideBySideLinesStub } from "./MapSideBySideLinesStub";
import { SideBySideLineState } from "../../src/app/file-diff/file-lines/SideBySideLineState";
import type { FileLinesState } from "../../src/app/file-diff/file-lines/SideBySideLineState";
import type { FileLine, GroupOfLines, UnMovedFileLine } from "../../src/app/file-diff/types";
import type { SynchronizedLineHandles } from "../../src/app/file-diff/file-lines/SideBySideLineMapper";
import { FileLineStub } from "./FileLineStub";
import { GroupOfLinesStub } from "./GroupOfLinesStub";
import { FileLineHandleStub } from "./FileLineHandleStub";

export interface StubFileLinesState {
    getState: () => FileLinesState;
    getFileLines: () => FileLine[];
}

function stubMissingFileLinesAtTheBeginningIfNeeded(file_lines: FileLine[]): UnMovedFileLine[] {
    if (file_lines.length === 0 || file_lines[0].unidiff_offset === 1) {
        return [];
    }

    const new_lines: UnMovedFileLine[] = [];
    for (let unidiff_offset = 1; unidiff_offset < file_lines[0].unidiff_offset; unidiff_offset++) {
        new_lines.push(
            FileLineStub.buildUnMovedFileLine(unidiff_offset, unidiff_offset, unidiff_offset),
        );
    }

    return new_lines;
}

export const FileLinesStateStub = (
    file_lines: FileLine[],
    groups_of_lines: GroupOfLines[],
    line_to_handles_map: Map<FileLine, SynchronizedLineHandles>,
): StubFileLinesState => {
    const missing_lines = stubMissingFileLinesAtTheBeginningIfNeeded(file_lines);
    if (missing_lines) {
        groups_of_lines.unshift(GroupOfLinesStub.buildGroupOfUnMovedLines(missing_lines));

        missing_lines.forEach((line) => {
            line_to_handles_map.set(line, {
                left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
            });
        });
    }

    const all_file_lines = [...missing_lines, ...file_lines];
    const state = SideBySideLineState(
        all_file_lines,
        GroupSideBySideLinesStub().withGroupsOfLines(groups_of_lines),
        MapSideBySideLinesStub().withSideBySideLineMap(line_to_handles_map),
    );

    return {
        getState: (): FileLinesState => state,
        getFileLines: (): FileLine[] => all_file_lines,
    };
};
