/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import type { Project } from "../store/types";
import { ProjectsSorter } from "./ProjectsSorter";

describe("ProjectsSorter", () => {
    it("sorted_projects should return the projects alphabetically sorted", () => {
        const projects: Project[] = [
            {
                id: 105,
                label: "Scrum",
            },
            {
                id: 106,
                label: "Git",
            },
            {
                id: 107,
                label: "Kanban",
            },
        ];

        expect(
            ProjectsSorter.sortProjectsAlphabetically(projects).map(({ id }) => id)
        ).toStrictEqual([106, 107, 105]);
    });
});
