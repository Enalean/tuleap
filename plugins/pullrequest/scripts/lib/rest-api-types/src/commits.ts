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

import type { User } from "@tuleap/core-rest-api-types";
import type { CommitBuildStatus } from "@tuleap/plugin-pullrequest-constants";

export type CommitStatus = {
    readonly name: CommitBuildStatus;
    readonly date: string;
};

export type PullRequestCommit = {
    readonly id: string;
    readonly html_url: string;
    readonly title: string;
    readonly author_name: string;
    readonly authored_date: string;
    readonly committed_date: string;
    readonly message: string;
    readonly author_email: string;
    readonly author: User | null;
    readonly commit_status: CommitStatus | null;
};
