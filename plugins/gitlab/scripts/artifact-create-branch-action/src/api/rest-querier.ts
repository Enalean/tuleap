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

import { getJSON, postJSON, post, uri } from "@tuleap/fetch-result";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";

export const postGitlabBranch = (
    gitlab_integration_id: number,
    artifact_id: number,
    reference: string,
): ResultAsync<GitLabIntegrationCreatedBranchInformation, Fault> =>
    postJSON<GitLabIntegrationCreatedBranchInformation>(uri`/api/v1/gitlab_branch`, {
        gitlab_integration_id: gitlab_integration_id,
        artifact_id: artifact_id,
        reference: reference,
    });

export const postGitlabMergeRequest = (
    gitlab_integration_id: number,
    artifact_id: number,
    source_branch: string,
): ResultAsync<void, Fault> =>
    post(
        uri`/api/v1/gitlab_merge_request`,
        {},
        { gitlab_integration_id, artifact_id, source_branch },
    ).map(() => {
        // ignore response
    });

export interface GitLabIntegrationBranchInformation {
    readonly default_branch: string;
}

export interface GitLabIntegrationCreatedBranchInformation {
    readonly branch_name: string;
}

export const getGitLabRepositoryBranchInformation = (
    gitlab_integration_id: number,
): ResultAsync<GitLabIntegrationBranchInformation, Fault> =>
    getJSON<GitLabIntegrationBranchInformation>(
        uri`/api/v1/gitlab_repositories/${gitlab_integration_id}/branches`,
    );
