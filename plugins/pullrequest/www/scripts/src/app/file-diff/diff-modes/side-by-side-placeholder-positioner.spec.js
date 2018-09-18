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

import { getDisplayAboveLineForWidget } from "./side-by-side-placeholder-positioner.js";
import {
    rewire$getGroupOfLine,
    rewire$getLineOfHandle,
    restore
} from "./side-by-side-lines-state.js";
import { DELETED_GROUP } from "./side-by-side-line-grouper.js";

describe("placeholder positioner", () => {
    let getGroupOfLine, getLineOfHandle;

    beforeEach(() => {
        getGroupOfLine = jasmine.createSpy("getGroupOfLine");
        rewire$getGroupOfLine(getGroupOfLine);

        getLineOfHandle = jasmine.createSpy("getLineOfHandle");
        rewire$getLineOfHandle(getLineOfHandle);
    });

    afterEach(() => {
        restore();
    });

    describe("getDisplayAboveLineForWidget()", () => {
        it("Given a handle, when the line is part of a deleted group, then it should return true", () => {
            const handle = {};
            const line = { unidiff_offset: 666 };

            getLineOfHandle.and.returnValue(line);
            getGroupOfLine.withArgs(line).and.returnValue({ type: DELETED_GROUP });

            const should_display_above = getDisplayAboveLineForWidget(handle);

            expect(should_display_above).toBe(true);
        });

        it("Given a handle without line, then it should return false", () => {
            const handle = {};

            getLineOfHandle.and.returnValue(null);

            const should_display_above = getDisplayAboveLineForWidget(handle);

            expect(should_display_above).toBe(false);
        });
    });
});
