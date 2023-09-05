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

import { DELETED_GROUP } from "../../types";
import type { FileLinesState } from "../../file-lines/SideBySideLineState";
import type { FileLineHandle } from "../../types-codemirror-overriden";

export interface PositionPlaceholder {
    getDisplayAboveLineForWidget: (handle: FileLineHandle) => boolean;
}

export const SideBySidePlaceholderPositioner = (
    file_lines_state: FileLinesState,
): PositionPlaceholder => ({
    getDisplayAboveLineForWidget(handle: FileLineHandle): boolean {
        const line_of_handle = file_lines_state.getLineOfHandle(handle);
        if (!line_of_handle) {
            return false;
        }

        const line_group = file_lines_state.getGroupOfLine(line_of_handle);
        return line_group !== null && line_group.type === DELETED_GROUP;
    },
});
