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

import { describe, expect, it, vi } from "vitest";
import * as rest_querier from "../../api/rest-querier";
import { loadProjectUserGroups } from "./ugroups";
import type { RootState, UserGroup } from "../../type";
import { okAsync } from "neverthrow";
import type { ActionContext } from "vuex";

describe("User groups", () => {
    it("filters special service user groups from the list", async () => {
        const getProjectUserGroupsSpy = vi.spyOn(rest_querier, "getProjectUserGroups");

        const project_members_ugroup: UserGroup = {
            id: "102_3",
            label: "Project members",
        };
        const project_special_service_ugroup: UserGroup = {
            id: "102_15",
            label: "Tracker admin",
        };
        const project_static_ugroup: UserGroup = {
            id: "130",
            label: "My group",
        };

        getProjectUserGroupsSpy.mockReturnValue(
            okAsync([
                project_members_ugroup,
                project_special_service_ugroup,
                project_static_ugroup,
            ]),
        );

        const filtered_ugroups = await loadProjectUserGroups(
            {} as ActionContext<RootState, RootState>,
            102,
        );

        expect(filtered_ugroups.isOk()).toBe(true);
        expect(filtered_ugroups.unwrapOr(null)).toEqual([
            project_members_ugroup,
            project_static_ugroup,
        ]);
    });
});
