/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { RetrieveProjects } from "../../../../domain/fields/link-field/creation/RetrieveProjects";
import { ProjectsCache } from "./ProjectsCache";
import { RetrieveProjectsStub } from "../../../../../tests/stubs/RetrieveProjectsStub";
import type { Project } from "../../../../domain/Project";
import { ProjectStub } from "../../../../../tests/stubs/ProjectStub";

describe(`ProjectsCache`, () => {
    let first_project: Project, second_project: Project;

    beforeEach(() => {
        first_project = ProjectStub.withDefaults({ id: 120 });
        second_project = ProjectStub.withDefaults({ id: 130 });
    });

    const getCache = (): RetrieveProjects => {
        const actual_retriever = RetrieveProjectsStub.withSuccessiveProjects(
            [first_project, second_project],
            [],
        );
        return ProjectsCache(actual_retriever);
    };

    it(`delegates to another retriever the first call,
        and on later calls it returns the cached result`, async () => {
        const cache = getCache();
        const first_call_result = await cache.getProjects();
        if (!first_call_result.isOk()) {
            throw Error("Expected an Ok");
        }
        expect(first_call_result.value).toHaveLength(2);
        expect(first_call_result.value).toContain(first_project);
        expect(first_call_result.value).toContain(second_project);

        const second_call_result = await cache.getProjects();
        if (!second_call_result.isOk()) {
            throw Error("Expected an Ok");
        }
        expect(second_call_result.value).toStrictEqual(first_call_result.value);
    });
});
