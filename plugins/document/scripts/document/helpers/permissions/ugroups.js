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

import { getProjectUserGroups } from "../../api/rest-querier.js";

const PROJECT_MEMBERS_ID = "3";
const PROJECT_ADMINS_ID = "4";

function isUGroupAServiceSpecialUGroup(project_id, ugroup) {
    return (
        ugroup.id.includes("_") &&
        ugroup.id !== `${project_id}_${PROJECT_MEMBERS_ID}` &&
        ugroup.id !== `${project_id}_${PROJECT_ADMINS_ID}`
    );
}

export async function getProjectUserGroupsWithoutServiceSpecialUGroups(project_id) {
    const ugroups = await getProjectUserGroups(project_id);

    const filtered_groups = [];

    ugroups.forEach((ugroup) => {
        if (isUGroupAServiceSpecialUGroup(project_id, ugroup)) {
            return;
        }
        filtered_groups.push(ugroup);
    });

    return filtered_groups;
}
