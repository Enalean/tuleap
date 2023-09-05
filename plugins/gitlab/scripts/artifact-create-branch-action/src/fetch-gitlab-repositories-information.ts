/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";
import type { GitLabIntegrationBranchInformation } from "./api/rest-querier";
import { getGitLabRepositoryBranchInformation } from "./api/rest-querier";

const MAX_CONCURRENT_REQUESTS = 5;

export interface GitlabIntegration {
    gitlab_repository_url: string;
    id: number;
    name: string;
    create_branch_prefix: string;
}
export type GitlabIntegrationWithDefaultBranch = Readonly<
    GitlabIntegration & { default_branch: string }
>;

export function getGitlabRepositoriesWithDefaultBranches(
    integrations: ReadonlyArray<GitlabIntegration>,
): Promise<ReadonlyArray<GitlabIntegrationWithDefaultBranch>> {
    return limitConcurrencyPool(
        MAX_CONCURRENT_REQUESTS,
        integrations,
        fetchGitlabRepositoryWithDefaultBranch,
    );
}

function fetchGitlabRepositoryWithDefaultBranch(
    gitlab_integration: GitlabIntegration,
): Promise<GitlabIntegrationWithDefaultBranch> {
    return getGitLabRepositoryBranchInformation(gitlab_integration.id).match(
        (
            branch_information: GitLabIntegrationBranchInformation,
        ): GitlabIntegrationWithDefaultBranch => {
            return { ...gitlab_integration, default_branch: branch_information.default_branch };
        },
        () => {
            return { ...gitlab_integration, default_branch: "" };
        },
    );
}
