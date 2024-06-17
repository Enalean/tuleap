/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { beforeEach, describe, expect, it } from "vitest";
import type { ProjectInfo } from "../type";
import type { RetrieveProjects } from "../domain/RetrieveProjects";
import { RetrieveProjectsStub } from "../../tests/stubs/RetrieveProjectsStub";
import { ProjectsCache } from "./ProjectsCache";
import { ProjectInfoStub } from "../../tests/stubs/ProjectInfoStub";

describe("ProjectsCache", () => {
    let first_project: ProjectInfo, second_project: ProjectInfo;

    beforeEach(() => {
        first_project = ProjectInfoStub.withId(101);
        second_project = ProjectInfoStub.withId(102);
    });

    const getCache = (): RetrieveProjects => {
        const actual_retriever = RetrieveProjectsStub.withSuccessiveProjects(
            [first_project, second_project],
            [],
        );
        return ProjectsCache(actual_retriever);
    };

    it(`delegates to another retriever the first call,
        and on later calls it will return the cached result`, async () => {
        const cache = getCache();
        const first_call_result = await cache.getSortedProjectsIAmMemberOf();
        if (!first_call_result.isOk()) {
            throw Error("Expected an Ok");
        }
        expect(first_call_result.value).toStrictEqual([first_project, second_project]);

        const second_call_result = await cache.getSortedProjectsIAmMemberOf();
        if (!second_call_result.isOk()) {
            throw Error("Expected an Ok");
        }
        expect(second_call_result.value).toStrictEqual(first_call_result.value);
    });
});
