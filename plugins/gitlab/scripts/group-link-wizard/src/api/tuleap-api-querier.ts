/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import { postJSON, uri } from "@tuleap/fetch-result";

export const linkGitlabGroupWithTuleap = (
    current_project_id: number,
    group_id: number,
    server_url: string,
    token: string,
    create_branch_prefix: string,
    allow_artifact_closure: boolean,
): ResultAsync<void, Fault> => {
    return postJSON(uri`/api/v1/gitlab_groups`, {
        project_id: current_project_id,
        gitlab_group_id: group_id,
        gitlab_server_url: server_url,
        gitlab_token: token,
        create_branch_prefix,
        allow_artifact_closure,
    });
};
