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
import { decodeJSON, postJSON, getAllJSON } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";

interface RecursiveGetProjectRepositories {
    readonly repositories: ReadonlyArray<GitRepository>;
}

export const getProjectRepositories = (
    project_id: number,
    branch_name_preview: string
): ResultAsync<readonly GitRepository[], Fault> =>
    getAllJSON<RecursiveGetProjectRepositories, GitRepository>(
        `/api/v1/projects/${project_id}/git`,
        {
            params: {
                fields: "basic",
                limit: 50,
                query: JSON.stringify({
                    scope: "project",
                    allow_creation_of_branch: branch_name_preview,
                }),
            },
            getCollectionCallback: (payload) => payload.repositories,
        }
    );

export interface GitCreateBranchResponse {
    readonly html_url: string;
}

export const postGitBranch = (
    repository_id: number,
    branch_name: string,
    reference: string
): ResultAsync<GitCreateBranchResponse, Fault> =>
    postJSON(`/api/v1/git/${encodeURIComponent(repository_id)}/branches`, {
        branch_name,
        reference,
    }).andThen((response) => decodeJSON<GitCreateBranchResponse>(response));

export const postPullRequestOnDefaultBranch = (
    repository: GitRepository,
    branch_name: string
): ResultAsync<Response, Fault> =>
    postJSON(`/api/v1/pull_requests`, {
        repository_id: repository.id,
        repository_dest_id: repository.id,
        branch_src: branch_name,
        branch_dest: repository.default_branch,
    });
