/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { post } from "@tuleap/tlp-fetch";

export async function postGitlabBranch(
    gitlab_integration_id: number,
    artifact_id: number,
    branch_name: string,
    reference: string
): Promise<void> {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify({
        gitlab_integration_id: gitlab_integration_id,
        artifact_id: artifact_id,
        branch_name: branch_name,
        reference: reference,
    });

    await post("/api/v1/gitlab_branch", {
        headers: headers,
        body: body,
    });
}
