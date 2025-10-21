/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { TYPE_FOLDER } from "../../constants";
import * as permissions_groups from "../../helpers/permissions/ugroups";
import { loadProjectUserGroupsIfNeeded } from "./permissions-actions";
import type { Folder, RootState } from "../../type";
import type { ActionContext } from "vuex";
import type { PermissionsState } from "./permissions-default-state";

describe("loadProjectUserGroupsIfNeeded", () => {
    let context: ActionContext<PermissionsState, RootState>;

    beforeEach(() => {
        context = {
            commit: vi.fn(),
            rootState: {
                current_folder: { id: 123, type: TYPE_FOLDER } as Folder,
                permissions: { project_ugroups: null } as PermissionsState,
            } as RootState,
        } as unknown as ActionContext<PermissionsState, RootState>;
    });

    it("Retrieve the project user groups when they are never been loaded", async () => {
        const getProjectUserGroupsWithoutServiceSpecialUGroupsSpy = vi.spyOn(
            permissions_groups,
            "getProjectUserGroupsWithoutServiceSpecialUGroups",
        );
        const project_ugroups = [{ id: "102_3", label: "Project members" }];
        getProjectUserGroupsWithoutServiceSpecialUGroupsSpy.mockReturnValue(
            Promise.resolve(project_ugroups),
        );

        await loadProjectUserGroupsIfNeeded(context, 102);

        expect(getProjectUserGroupsWithoutServiceSpecialUGroupsSpy).toHaveBeenCalled();
        expect(context.commit).toHaveBeenCalledWith("setProjectUserGroups", project_ugroups);
    });

    it("Does not retrieve the project user groups when they have already been retrieved", async () => {
        const getProjectUserGroupsWithoutServiceSpecialUGroupsSpy = vi.spyOn(
            permissions_groups,
            "getProjectUserGroupsWithoutServiceSpecialUGroups",
        );

        context.rootState.permissions.project_ugroups = [{ id: "102_3", label: "Project members" }];

        await loadProjectUserGroupsIfNeeded(context, 102);

        expect(getProjectUserGroupsWithoutServiceSpecialUGroupsSpy).not.toHaveBeenCalled();
    });
});
