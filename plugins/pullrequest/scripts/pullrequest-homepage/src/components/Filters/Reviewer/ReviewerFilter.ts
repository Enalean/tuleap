/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { BasePullRequestsListFilter } from "../PullRequestsListFilter";

export type ReviewerFilter = "reviewer";
export const TYPE_FILTER_REVIEWER: ReviewerFilter = "reviewer";

export type PullRequestReviewerFilter = BasePullRequestsListFilter<User> & {
    type: ReviewerFilter;
    is_unique: true;
};

type BuildReviewerFilter = {
    fromReviewer(reviewer: User): PullRequestReviewerFilter;
};

export const ReviewerFilterBuilder = (
    $gettext: (string: string) => string,
): BuildReviewerFilter => ({
    fromReviewer: (reviewer: User): PullRequestReviewerFilter => ({
        id: reviewer.id,
        type: TYPE_FILTER_REVIEWER,
        label: `${$gettext("Reviewer")}: ${reviewer.display_name}`,
        value: reviewer,
        is_unique: true,
    }),
});
