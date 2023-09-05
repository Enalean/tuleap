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
import { FileLineStub } from "../../../../tests/stubs/FileLineStub";
import { SideBySideCodeMirrorsContentManager } from "./SideBySideCodeMirrorsContentManager";

describe("side-by-side-code-mirrors-content-manager", () => {
    it(`Given the lines of a file and the two codemirror editors
        Then the left codemirror should have its content set with deleted and unmoved lines
        And the right codemirror should have its content set with added and unmoved lines`, () => {
        let left_code_mirror_content = "";
        let right_code_mirror_content = "";

        const left_codemirror = {
            setValue: jest.fn().mockImplementation((content: string) => {
                left_code_mirror_content = content;
            }),
        } as unknown as Editor;

        const right_codemirror = {
            setValue: jest.fn().mockImplementation((content: string) => {
                right_code_mirror_content = content;
            }),
        } as unknown as Editor;

        const file_lines = [
            FileLineStub.buildRemovedLine(1, 1, "# Project's README"),
            FileLineStub.buildAddedLine(2, 1, "# Guinea Pig"),
            FileLineStub.buildUnMovedFileLine(3, 2, 2, ""),
            FileLineStub.buildUnMovedFileLine(4, 3, 3, "This is the project's description"),
            FileLineStub.buildUnMovedFileLine(5, 4, 4, "You will find interesting stuff inside"),
            FileLineStub.buildUnMovedFileLine(6, 5, 5, ""),
            FileLineStub.buildRemovedLine(7, 6, "Hello world !"),
        ];

        SideBySideCodeMirrorsContentManager(file_lines, left_codemirror, right_codemirror);

        expect(left_code_mirror_content).toStrictEqual(
            "# Project's README\n" +
                "\n" +
                "This is the project's description\n" +
                "You will find interesting stuff inside\n" +
                "\n" +
                "Hello world !",
        );

        expect(right_code_mirror_content).toStrictEqual(
            "# Guinea Pig\n" +
                "\n" +
                "This is the project's description\n" +
                "You will find interesting stuff inside\n",
        );
    });
});
