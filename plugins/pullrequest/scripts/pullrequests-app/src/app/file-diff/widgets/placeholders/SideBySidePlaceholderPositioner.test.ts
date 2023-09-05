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

import { FileLineStub } from "../../../../../tests/stubs/FileLineStub";
import { GroupOfLinesStub } from "../../../../../tests/stubs/GroupOfLinesStub";
import { FileLineHandleStub } from "../../../../../tests/stubs/FileLineHandleStub";
import { FileLinesStateStub } from "../../../../../tests/stubs/FileLinesStateStub";
import type { FileLine } from "../../types";
import { SideBySidePlaceholderPositioner } from "./SideBySidePlaceholderPositioner";

describe("placeholder positioner", () => {
    describe("getDisplayAboveLineForWidget()", () => {
        it("Given a handle, when the line is part of a deleted group, then it should return true", () => {
            const handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            const line = FileLineStub.buildRemovedLine(3, 3);
            const state = FileLinesStateStub(
                [line],
                [GroupOfLinesStub.buildGroupOfRemovedLines([line])],
                new Map([
                    [
                        line as FileLine,
                        {
                            left_handle: handle,
                            right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                        },
                    ],
                ]),
            ).getState();

            const should_display_above =
                SideBySidePlaceholderPositioner(state).getDisplayAboveLineForWidget(handle);

            expect(should_display_above).toBe(true);
        });

        it("Given a handle without line, then it should return false", () => {
            const handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            const state = FileLinesStateStub([], [], new Map([])).getState();

            const should_display_above =
                SideBySidePlaceholderPositioner(state).getDisplayAboveLineForWidget(handle);

            expect(should_display_above).toBe(false);
        });
    });
});
