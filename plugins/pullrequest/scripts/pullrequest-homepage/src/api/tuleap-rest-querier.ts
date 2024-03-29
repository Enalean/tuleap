/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import type {
    Branch,
    ProjectLabel,
    ProjectLabelsCollection,
    PullRequest,
    User,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import { uri, getAllJSON } from "@tuleap/fetch-result";
import type { PullRequestsListFilter } from "../components/Filters/PullRequestsListFilter";
import type { PullRequestSortOrder } from "../injection-symbols";
import { buildQueryFromFilters } from "./get-pull-requests-query-builder";

type PullRequestCollection = {
    readonly collection: PullRequest[];
};

export const fetchAllPullRequests = (
    repository_id: number,
    current_user_id: number,
    filters: PullRequestsListFilter[],
    are_closed_pull_requests_shown: boolean,
    are_pull_requests_related_to_me_shown: boolean,
    sort_order: PullRequestSortOrder,
): ResultAsync<readonly PullRequest[], Fault> =>
    getAllJSON<PullRequest, PullRequestCollection>(
        uri`/api/v1/git/${repository_id}/pull_requests`,
        {
            params: {
                limit: 50,
                order: sort_order,
                query: buildQueryFromFilters(
                    current_user_id,
                    filters,
                    are_closed_pull_requests_shown,
                    are_pull_requests_related_to_me_shown,
                ),
            },
            getCollectionCallback: (payload) => payload.collection,
        },
    );

export const fetchPullRequestLabels = (
    pull_request_id: number,
): ResultAsync<readonly ProjectLabel[], Fault> => {
    return getAllJSON<ProjectLabel, ProjectLabelsCollection>(
        uri`/api/v1/pull_requests/${pull_request_id}/labels`,
        {
            params: {
                limit: 50,
            },
            getCollectionCallback: (payload) => {
                return payload.labels;
            },
        },
    );
};

export const fetchPullRequestsAuthors = (
    repository_id: number,
): ResultAsync<readonly User[], Fault> => {
    return getAllJSON(uri`/api/v1/git/${repository_id}/pull_requests_authors`, {
        params: { limit: 50 },
    });
};

export const fetchProjectLabels = (
    project_id: number,
): ResultAsync<readonly ProjectLabel[], Fault> => {
    return getAllJSON<ProjectLabel, ProjectLabelsCollection>(
        uri`/api/v1/projects/${project_id}/labels`,
        {
            params: { limit: 50 },
            getCollectionCallback: (payload) => payload.labels,
        },
    );
};

export const fetchRepositoryBranches = (
    repository_id: number,
): ResultAsync<readonly Branch[], Fault> => {
    return getAllJSON(uri`/api/v1/git/${repository_id}/branches`, {
        params: { limit: 50 },
    });
};

export const fetchRepositoryReviewers = (
    repository_id: number,
): ResultAsync<readonly User[], Fault> => {
    return getAllJSON(uri`/api/v1/git/${repository_id}/pull_requests_reviewers`, {
        params: { limit: 50 },
    });
};
