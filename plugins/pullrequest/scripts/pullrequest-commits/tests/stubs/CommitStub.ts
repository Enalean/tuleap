/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import type {
    CommitStatus,
    PullRequestCommit,
    User,
} from "@tuleap/plugin-pullrequest-rest-api-types";

const buildBaseCommit = (commit_id: string): PullRequestCommit => ({
    id: commit_id,
    author_name: "John Doe",
    authored_date: "2025-10-10T16:34:29+01:00",
    committed_date: "2025-10-10T16:34:29+01:00",
    title: "Do some stuff",
    message: "Some stuff has been done",
    author_email: "john.doe@example.com",
    author: null,
    html_url: `example.com?a=commit&h=${commit_id}`,
    commit_status: null,
});

export const CommitStub = {
    withDefaults: (commit_id: string): PullRequestCommit => buildBaseCommit(commit_id),
    fromExistingAuthor: (commit_id: string, author: User): PullRequestCommit => ({
        ...buildBaseCommit(commit_id),
        author,
    }),
    fromUnknownAuthor: (
        commit_id: string,
        unknown_author: { author_name: string; author_email: string },
    ): PullRequestCommit => ({
        ...buildBaseCommit(commit_id),
        ...unknown_author,
    }),
    withCIBuildStatus: (commit_id: string, commit_status: CommitStatus): PullRequestCommit => ({
        ...buildBaseCommit(commit_id),
        commit_status,
    }),
    withAuthoredDate: (commit_id: string, authored_date: string): PullRequestCommit => ({
        ...buildBaseCommit(commit_id),
        authored_date,
    }),
};
