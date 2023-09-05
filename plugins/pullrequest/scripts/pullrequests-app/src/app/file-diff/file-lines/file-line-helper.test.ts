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

import { isAnUnmovedLine, isAnAddedLine, isARemovedLine } from "./file-line-helper";
import { FileLineStub } from "../../../../tests/stubs/FileLineStub";

describe("file-line-helper", () => {
    it.each([
        [false, "undefined", undefined],
        [false, "an added line", FileLineStub.buildAddedLine(1, 1)],
        [false, "a removed line", FileLineStub.buildRemovedLine(1, 1)],
        [true, "an unmoved line", FileLineStub.buildUnMovedFileLine(1, 1, 1)],
    ])(
        "isAnUnmovedLine() should return %s when the line is %s",
        (is_unmoved, line_description, line) => {
            expect(isAnUnmovedLine(line)).toBe(is_unmoved);
        },
    );

    it.each([
        [false, "undefined", undefined],
        [true, "an added line", FileLineStub.buildAddedLine(1, 1)],
        [false, "a removed line", FileLineStub.buildRemovedLine(1, 1)],
        [false, "an unmoved line", FileLineStub.buildUnMovedFileLine(1, 1, 1)],
    ])(
        "isAnAddedLine() should return %s when the line is %s",
        (is_added, line_description, line) => {
            expect(isAnAddedLine(line)).toBe(is_added);
        },
    );

    it.each([
        [false, "undefined", undefined],
        [false, "an added line", FileLineStub.buildAddedLine(1, 1)],
        [true, "a removed line", FileLineStub.buildRemovedLine(1, 1)],
        [false, "an unmoved line", FileLineStub.buildUnMovedFileLine(1, 1, 1)],
    ])(
        "isARemovedLine() should return %s when the line is %s",
        (is_removed, line_description, line) => {
            expect(isARemovedLine(line)).toBe(is_removed);
        },
    );
});
