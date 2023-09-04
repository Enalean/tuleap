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

import { post, recursiveGet } from "@tuleap/tlp-fetch";

export function getBranches(repository_id) {
    return recursiveGet("/api/git/" + repository_id + "/branches", {
        params: {
            limit: 50,
        },
    });
}

export async function createPullrequest(
    source_repository_id,
    source_branch_name,
    destination_repository_id,
    destination_branch_name,
) {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify({
        repository_id: source_repository_id,
        branch_src: source_branch_name,
        repository_dest_id: destination_repository_id,
        branch_dest: destination_branch_name,
    });

    const response = await post("/api/pull_requests", { headers, body });

    return response.json();
}
