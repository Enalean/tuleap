/**
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

import { addOriginalFilenameExtension } from "./add-original-filename-extension";

describe("add-original-filename-extension", () => {
    it.each([
        ["", "myfile"],
        ["toto", "myfile"],
        ["toto.txt", "myfile.txt"],
        [".profile", "myfile"],
        ["with.multiple.dots.pdf", "myfile.pdf"],
    ])(
        `Given dropped file is %s, then expected filename is %s`,
        (dropped_filename: string, expected: string): void => {
            const file = new File([], dropped_filename);

            expect(addOriginalFilenameExtension("myfile", file)).toBe(expected);
        },
    );

    it("does not throw when filename is not defined", () => {
        expect(addOriginalFilenameExtension("myfile", undefined)).toBe("myfile");
    });
});
