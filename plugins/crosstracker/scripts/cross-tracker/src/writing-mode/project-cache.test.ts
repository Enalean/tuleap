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

import type { ProjectReference } from "@tuleap/core-rest-api-types";
import { getSortedProjectsIAmMemberOf as getProjects } from "./projects-cache";
import * as rest_querier from "../api/rest-querier";

describe("getSortedProjectsIAmMemberOf", () => {
    it("Returns the projects I'm member of", async () => {
        const spyRESTgetSortedProjectsIAmMemberOf = jest.spyOn(
            rest_querier,
            "getSortedProjectsIAmMemberOf"
        );

        const expected_project_list = [
            { id: 101, label: "project A", uri: "uri/to/projectA" } as ProjectReference,
            { id: 102, label: "project B", uri: "uri/to/projectB" } as ProjectReference,
        ];

        spyRESTgetSortedProjectsIAmMemberOf.mockImplementation(() =>
            Promise.resolve(expected_project_list)
        );

        const project_list = await getProjects();
        expect(expected_project_list).toStrictEqual(project_list);
    });
});
