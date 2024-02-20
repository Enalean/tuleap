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

import type { BasePullRequestsListFilter } from "../PullRequestsListFilter";

export type KeywordFilter = "keyword";
export const TYPE_FILTER_KEYWORD: KeywordFilter = "keyword";

export type PullRequestKeywordFilter = BasePullRequestsListFilter<string> & {
    type: KeywordFilter;
    is_unique: false;
};

export type BuildKeywordFilter = {
    fromKeyword(id: number, keyword: string): PullRequestKeywordFilter;
};

export const KeywordFilterBuilder = ($gettext: (string: string) => string): BuildKeywordFilter => ({
    fromKeyword: (id: number, keyword: string): PullRequestKeywordFilter => ({
        id,
        type: TYPE_FILTER_KEYWORD,
        label: `${$gettext("Keyword")}: ${keyword}`,
        value: keyword,
        is_unique: false,
    }),
});
