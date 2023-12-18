/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { getJSON, uri } from "@tuleap/fetch-result";

export type TrackerPermissions = Array<{
    readonly admin_quick_link: string;
    readonly tracker_name: string;
    readonly permissions: Array<{
        readonly permission_name: string;
        readonly granted_groups: Array<{
            readonly ugroup_name: string;
            readonly is_project_admin: boolean;
            readonly is_static: boolean;
            readonly is_custom: boolean;
        }>;
    }>;
}>;

export function getTrackerPermissions(
    project_id: string,
    selected_ugroup_id: string,
): ResultAsync<TrackerPermissions, Fault> {
    return getJSON<TrackerPermissions>(
        uri`/plugins/tracker/?group_id=${project_id}&selected_ugroup_id=${selected_ugroup_id}&func=permissions-per-group`,
    );
}
