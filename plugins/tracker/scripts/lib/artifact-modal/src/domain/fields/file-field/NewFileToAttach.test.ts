/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { NewFileToAttach } from "./NewFileToAttach";

describe(`NewFileToAttach`, () => {
    let file: NewFileToAttach;

    beforeEach(() => {
        file = NewFileToAttach.build();
    });

    it(`builds a new file to attach from scratch`, () => {
        expect(file.file).toBeUndefined();
        expect(file.description).toBe("");
    });

    it(`builds a new file to attach from a previous one and a new description`, () => {
        const DESCRIPTION = "Hydroidea";
        const new_file = NewFileToAttach.fromDescriptionAndPrevious(file, DESCRIPTION);

        expect(new_file).not.toBe(file);
        expect(new_file.description).toBe(DESCRIPTION);
        expect(new_file.file).toBeUndefined();
    });

    it(`builds a new file to attach from a previous one and a new File (to upload)`, () => {
        const file_to_upload = new File([], "a_file.txt");
        const new_file = NewFileToAttach.fromFileAndPrevious(file, file_to_upload);

        expect(new_file).not.toBe(file);
        expect(new_file.description).toBe("");
        expect(new_file.file).toBe(file_to_upload);
    });
});
