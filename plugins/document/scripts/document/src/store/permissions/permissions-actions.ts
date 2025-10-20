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

import type { ActionContext } from "vuex";
import type { RootState } from "../../type";
import type { PermissionsState } from "./permissions-default-state";
import { getProjectUserGroupsWithoutServiceSpecialUGroups } from "../../helpers/permissions/ugroups";

export const loadProjectUserGroupsIfNeeded = async (
    context: ActionContext<PermissionsState, RootState>,
    project_id: number,
): Promise<void> => {
    if (context.rootState.permissions.project_ugroups !== null) {
        return;
    }

    const project_ugroups = await getProjectUserGroupsWithoutServiceSpecialUGroups(project_id);

    context.commit("setProjectUserGroups", project_ugroups);
};
