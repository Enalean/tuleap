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

import type { AuthorFilter, PullRequestAuthorFilter } from "./Author/AuthorFilter";
import type { LabelFilter, PullRequestLabelFilter } from "./Labels/LabelFilter";
import type { KeywordFilter, PullRequestKeywordFilter } from "./Keywords/KeywordFilter";
import type {
    TargetBranchFilter,
    PullRequestTargetBranchFilter,
} from "./Branches/TargetBranchFilter";
import type { ReviewerFilter, PullRequestReviewerFilter } from "./Reviewer/ReviewerFilter";

export type PullRequestsListFilterType =
    | AuthorFilter
    | LabelFilter
    | KeywordFilter
    | TargetBranchFilter
    | ReviewerFilter;

export type BasePullRequestsListFilter<TypeOfFilterValue> = {
    id: number;
    type: PullRequestsListFilterType;
    label: string;
    value: TypeOfFilterValue;
    is_unique: boolean;
};

export type PullRequestsListFilter =
    | PullRequestAuthorFilter
    | PullRequestLabelFilter
    | PullRequestKeywordFilter
    | PullRequestTargetBranchFilter
    | PullRequestReviewerFilter;
