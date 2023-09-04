/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { put } from "@tuleap/tlp-fetch";
import type { ManageTeamIdsOfProgram } from "../type";

export function manageTeamOfProgram(manage_team: ManageTeamIdsOfProgram): Promise<Response> {
    return put(
        "/api/v1/projects/" + encodeURIComponent(manage_team.program_id) + "/program_teams",
        {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ team_ids: manage_team.team_ids }),
        },
    );
}
