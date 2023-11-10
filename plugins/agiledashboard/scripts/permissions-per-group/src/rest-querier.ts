/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import type { Fault } from "@tuleap/fault";
import { getJSON, uri } from "@tuleap/fetch-result";
import type { ResultAsync } from "neverthrow";

export type PlanningsPermissions = Array<{
    readonly quick_link: string;
    readonly name: string;
    readonly ugroups: Array<{
        readonly ugroup_name: string;
        readonly is_project_admin: boolean;
        readonly is_static: boolean;
        readonly is_custom: boolean;
    }>;
}>;

function getAgiledashboardPermissions(
    project_id: string,
    selected_ugroup_id: string,
): ResultAsync<
    {
        readonly plannings_permissions: PlanningsPermissions;
    },
    Fault
> {
    return getJSON<{ readonly plannings_permissions: PlanningsPermissions }>(
        uri`/plugins/agiledashboard/?group_id=${project_id}&selected_ugroup_id=${selected_ugroup_id}&action=permission-per-group`,
    );
}

export { getAgiledashboardPermissions };
