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

import type { GitRepository } from "../src/types";
import { recursiveGet } from "@tuleap/tlp-fetch";
import { postJSON } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";

interface RecursiveGetProjectRepositories {
    repositories: Array<GitRepository>;
}

export function getProjectRepositories(
    project_id: number,
    branch_name_preview: string
): Promise<ReadonlyArray<GitRepository>> {
    return recursiveGet(`/api/v1/projects/${encodeURIComponent(project_id)}/git`, {
        params: {
            fields: "basic",
            limit: 50,
            query: JSON.stringify({
                scope: "project",
                allow_creation_of_branch: branch_name_preview,
            }),
        },
        getCollectionCallback: (payload: RecursiveGetProjectRepositories) => payload.repositories,
    });
}

export const postGitBranch = (
    repository_id: number,
    branch_name: string,
    reference: string
): ResultAsync<void, Fault> =>
    postJSON(`/api/v1/git/${encodeURIComponent(repository_id)}/branches`, {
        branch_name,
        reference,
    }).map(() => {
        // ignore response
    });
