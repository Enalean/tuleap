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

import { getJSON, getAllJSON, uri, patchJSON, put, patch } from "@tuleap/fetch-result";
import type {
    PullRequest,
    User,
    TimelineItem,
    ReviewersCollection,
    ProjectLabelsCollection,
    ProjectLabel,
    PatchPullRequestLabelsPayload,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import {
    PULL_REQUEST_STATUS_ABANDON,
    PULL_REQUEST_STATUS_MERGED,
    PULL_REQUEST_STATUS_REVIEW,
} from "@tuleap/plugin-pullrequest-constants";

interface TimelineItemsCollection {
    collection: ReadonlyArray<TimelineItem>;
}

export const fetchPullRequestInfo = (pull_request_id: number): ResultAsync<PullRequest, Fault> => {
    return getJSON(uri`/api/v1/pull_requests/${pull_request_id}`);
};

export const fetchUserInfo = (user_id: number): ResultAsync<User, Fault> => {
    return getJSON(uri`/api/v1/users/${user_id}`);
};

export const fetchPullRequestTimelineItems = (
    pull_request_id: number
): ResultAsync<readonly TimelineItem[], Fault> => {
    return getAllJSON<TimelineItemsCollection, TimelineItem>(
        uri`/api/v1/pull_requests/${pull_request_id}/timeline`,
        {
            params: { limit: 50, offset: 0 },
            getCollectionCallback: (
                payload: TimelineItemsCollection
            ): ReadonlyArray<TimelineItem> => payload.collection,
        }
    );
};

export const patchTitle = (
    pull_request_id: number,
    updated_title: string
): ResultAsync<PullRequest, Fault> => {
    return patchJSON(uri`/api/v1/pull_requests/${pull_request_id}`, {
        title: updated_title,
    });
};

export const fetchReviewersInfo = (
    pull_request_id: number
): ResultAsync<ReviewersCollection, Fault> => {
    return getJSON(uri`/api/v1/pull_requests/${pull_request_id}/reviewers`);
};

export const mergePullRequest = (pull_request_id: number): ResultAsync<PullRequest, Fault> => {
    return patchJSON<PullRequest>(uri`/api/v1/pull_requests/${pull_request_id}`, {
        status: PULL_REQUEST_STATUS_MERGED,
    });
};

export const reopenPullRequest = (pull_request_id: number): ResultAsync<PullRequest, Fault> => {
    return patchJSON<PullRequest>(uri`/api/v1/pull_requests/${pull_request_id}`, {
        status: PULL_REQUEST_STATUS_REVIEW,
    });
};

export const abandonPullRequest = (pull_request_id: number): ResultAsync<PullRequest, Fault> => {
    return patchJSON<PullRequest>(uri`/api/v1/pull_requests/${pull_request_id}`, {
        status: PULL_REQUEST_STATUS_ABANDON,
    });
};

export const fetchMatchingUsers = (query: string): ResultAsync<User[], Fault> => {
    return getJSON(uri`/api/v1/users`, {
        params: {
            query,
            limit: 10,
            offset: 0,
        },
    });
};

export const putReviewers = (
    pull_request_id: number,
    reviewers: ReadonlyArray<User>
): ResultAsync<Response, Fault> => {
    return put(
        uri`/api/v1/pull_requests/${pull_request_id}/reviewers`,
        {},
        {
            users: reviewers.map(({ id }) => ({ id })),
        }
    );
};

export const fetchPullRequestLabels = (
    pull_request_id: number
): ResultAsync<readonly ProjectLabel[], Fault> => {
    return getAllJSON(uri`/api/v1/pull_requests/${pull_request_id}/labels`, {
        params: {
            limit: 50,
            offset: 0,
        },
        getCollectionCallback: (payload: ProjectLabelsCollection) => {
            return payload.labels;
        },
    });
};

export const fetchProjectLabels = (
    project_id: number
): ResultAsync<readonly ProjectLabel[], Fault> => {
    return getAllJSON(uri`/api/v1/projects/${project_id}/labels`, {
        params: {
            limit: 50,
            offset: 0,
        },
        getCollectionCallback: (payload: ProjectLabelsCollection) => {
            return payload.labels;
        },
    });
};

export const patchPullRequestLabels = (
    pull_request_id: number,
    added_labels: ReadonlyArray<number>,
    removed_labels: ReadonlyArray<number>
): ResultAsync<Response | null, Fault> => {
    if (added_labels.length === 0 && removed_labels.length === 0) {
        return okAsync(null);
    }

    const payload: PatchPullRequestLabelsPayload = {};
    if (added_labels.length > 0) {
        const added_labels_ids = added_labels.map((id) => ({ id }));
        payload.add = [added_labels_ids[0], ...added_labels_ids.slice(1)];
    }

    if (removed_labels.length > 0) {
        const removed_labels_ids = removed_labels.map((id) => ({ id }));
        payload.remove = [removed_labels_ids[0], ...removed_labels_ids.slice(1)];
    }

    return patch(
        uri`/api/v1/pull_requests/${encodeURIComponent(pull_request_id)}/labels`,
        {},
        payload
    );
};
