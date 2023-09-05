/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { post } from "@tuleap/tlp-fetch";
import type { Credentials, ProjectList, ProjectTrackerPayload, TrackerList, State } from "./type";
import type { ActionContext } from "vuex";

export async function getJiraProjectList(
    context: ActionContext<State, State>,
    credentials: Credentials,
): Promise<ProjectList[]> {
    const response = await post(
        "/plugins/tracker/" +
            encodeURIComponent(context.state.project_unix_name) +
            "/jira/project_list",
        {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ credentials }),
        },
    );

    return response.json();
}

export async function getJiraTrackerList(
    context: ActionContext<State, State>,
    project_tracker_payload: ProjectTrackerPayload,
): Promise<TrackerList[]> {
    const response = await post(
        "/plugins/tracker/" +
            encodeURIComponent(context.state.project_unix_name) +
            "/jira/" +
            encodeURIComponent(project_tracker_payload.project_key) +
            "/tracker_list",
        {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ credentials: project_tracker_payload.credentials }),
        },
    );

    return response.json();
}
