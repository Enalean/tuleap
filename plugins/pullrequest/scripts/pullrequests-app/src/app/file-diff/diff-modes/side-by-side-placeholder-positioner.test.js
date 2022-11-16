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

import { getDisplayAboveLineForWidget } from "./side-by-side-placeholder-positioner.js";
import * as side_by_side_lines_state from "./side-by-side-lines-state.js";
import { FileLineStub } from "../../../../tests/stubs/FileLineStub";
import { GroupOfLinesStub } from "../../../../tests/stubs/GroupOfLinesStub";
import { FileLineHandleStub } from "../../../../tests/stubs/FileLineHandleStub";

describe("placeholder positioner", () => {
    let getGroupOfLine, getLineOfHandle;

    beforeEach(() => {
        getGroupOfLine = jest.spyOn(side_by_side_lines_state, "getGroupOfLine");
        getLineOfHandle = jest.spyOn(side_by_side_lines_state, "getLineOfHandle");
    });

    describe("getDisplayAboveLineForWidget()", () => {
        it("Given a handle, when the line is part of a deleted group, then it should return true", () => {
            const handle = FileLineHandleStub.buildLineHandleWithNoWidgets();
            const line = FileLineStub.buildRemovedLine(666, 666);

            getLineOfHandle.mockReturnValue(line);
            getGroupOfLine.mockImplementation((l) => {
                if (line === l) {
                    return GroupOfLinesStub.buildGroupOfRemovedLines([line]);
                }
                throw new Error(l);
            });

            const should_display_above = getDisplayAboveLineForWidget(handle);

            expect(should_display_above).toBe(true);
        });

        it("Given a handle without line, then it should return false", () => {
            const handle = FileLineHandleStub.buildLineHandleWithNoWidgets();

            getLineOfHandle.mockReturnValue(null);

            const should_display_above = getDisplayAboveLineForWidget(handle);

            expect(should_display_above).toBe(false);
        });
    });
});
