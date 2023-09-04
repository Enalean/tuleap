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

import type { Editor } from "codemirror";
import type { FileLine, RightLine, LeftLine } from "../types";
import { isAnAddedLine, isAnUnmovedLine, isARemovedLine } from "../file-lines/file-line-helper";

export interface ManageCodeMirrorsContent {
    getLineInLeftCodeMirror: (line_number: number) => LeftLine;
    getLineInRightCodeMirror: (line_number: number) => RightLine;
    getLeftCodeMirrorEditor: () => Editor;
    getRightCodeMirrorEditor: () => Editor;
}

export const SideBySideCodeMirrorsContentManager = (
    file_lines: readonly FileLine[],
    left_code_mirror: Editor,
    right_code_mirror: Editor,
): ManageCodeMirrorsContent => {
    const left_lines = file_lines.filter(
        (line: FileLine): line is LeftLine => isAnUnmovedLine(line) || isARemovedLine(line),
    );

    const right_lines = file_lines.filter(
        (line: FileLine): line is RightLine => isAnUnmovedLine(line) || isAnAddedLine(line),
    );

    const left_content = left_lines.map((line: FileLine) => line.content).join("\n");
    const right_content = right_lines.map((line: FileLine) => line.content).join("\n");

    left_code_mirror.setValue(left_content);
    right_code_mirror.setValue(right_content);

    return {
        getLineInLeftCodeMirror: (line_number: number): LeftLine => left_lines[line_number],
        getLineInRightCodeMirror: (line_number: number): RightLine => right_lines[line_number],
        getLeftCodeMirrorEditor: (): Editor => left_code_mirror,
        getRightCodeMirrorEditor: (): Editor => right_code_mirror,
    };
};
