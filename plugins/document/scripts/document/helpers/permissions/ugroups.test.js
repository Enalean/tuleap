/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import * as rest_querier from "../../api/rest-querier.js";
import { getProjectUserGroupsWithoutServiceSpecialUGroups } from "./ugroups";

describe("User groups", () => {
    it("filters special service user groups from the list", async () => {
        const getProjectUserGroupsSpy = jest.spyOn(rest_querier, "getProjectUserGroups");

        const project_members_ugroup = {
            id: "102_3",
        };
        const project_special_service_ugroup = {
            id: "102_17",
        };
        const project_static_ugroup = {
            id: "130",
        };

        getProjectUserGroupsSpy.mockReturnValue([
            project_members_ugroup,
            project_special_service_ugroup,
            project_static_ugroup,
        ]);

        const filtered_ugroups = await getProjectUserGroupsWithoutServiceSpecialUGroups(102);

        expect(filtered_ugroups).toEqual([project_members_ugroup, project_static_ugroup]);
    });
});
