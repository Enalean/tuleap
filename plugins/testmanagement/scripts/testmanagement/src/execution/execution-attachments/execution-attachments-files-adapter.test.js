/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { buildFileInfo } from "./execution-attachments-files-adapter";

describe("execution-attachments-files-adapter", () => {
    it("should build the file info and return it", () => {
        const file_info = buildFileInfo({
            name: "bug.png",
            size: 19651,
            type: "image/png",
        });

        expect(file_info).toEqual({
            name: "bug.png",
            file_type: "image/png",
            file_size: 19651,
        });
    });

    it("should set the file type to application/octet-stream when it has no type", () => {
        const file_info = buildFileInfo({
            name: "random_bytes",
            size: 19651,
            type: "",
        });

        expect(file_info).toEqual({
            name: "random_bytes",
            file_type: "application/octet-stream",
            file_size: 19651,
        });
    });
});
