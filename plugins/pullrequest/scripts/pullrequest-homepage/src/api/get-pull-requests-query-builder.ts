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

import type { PullRequestsListFilter } from "../components/Filters/PullRequestsListFilter";
import { TYPE_FILTER_AUTHOR } from "../components/Filters/Author/AuthorFilter";
import { TYPE_FILTER_LABEL } from "../components/Filters/Labels/LabelFilter";
import { TYPE_FILTER_KEYWORD } from "../components/Filters/Keywords/KeywordFilter";
import { TYPE_FILTER_TARGET_BRANCH } from "../components/Filters/Branches/TargetBranchFilter";
import { TYPE_FILTER_REVIEWER } from "../components/Filters/Reviewer/ReviewerFilter";

const assignLabelsToQuery = (query: object, labels_ids: number[]): void => {
    if (labels_ids.length === 0) {
        return;
    }

    Object.assign(query, {
        labels: labels_ids.map((id) => ({ id })),
    });
};

const assignKeywordsToQuery = (query: object, keywords: string[]): void => {
    if (keywords.length === 0) {
        return;
    }

    Object.assign(query, {
        search: keywords.map((keyword) => ({ keyword })),
    });
};

export const buildQueryFromFilters = (
    filters: PullRequestsListFilter[],
    are_closed_pull_requests_shown: boolean,
): string => {
    const query = {};
    const labels_ids: number[] = [];
    const keywords: string[] = [];

    if (!are_closed_pull_requests_shown) {
        Object.assign(query, { status: "open" });
    }

    filters.forEach((filter) => {
        switch (filter.type) {
            case TYPE_FILTER_AUTHOR:
                Object.assign(query, {
                    authors: [{ id: filter.value.id }],
                });
                break;
            case TYPE_FILTER_LABEL:
                labels_ids.push(filter.value.id);
                break;
            case TYPE_FILTER_KEYWORD:
                keywords.push(filter.value);
                break;
            case TYPE_FILTER_TARGET_BRANCH:
                Object.assign(query, {
                    target_branches: [{ name: filter.value.name }],
                });
                break;
            case TYPE_FILTER_REVIEWER:
                Object.assign(query, {
                    reviewers: [{ id: filter.value.id }],
                });
                break;
            default:
                break;
        }
    });

    assignLabelsToQuery(query, labels_ids);
    assignKeywordsToQuery(query, keywords);

    return JSON.stringify(query);
};
