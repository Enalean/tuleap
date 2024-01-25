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

export const buildQueryFromFilters = (filters: PullRequestsListFilter[]): string => {
    const query = {};
    filters.forEach((filter) => {
        if (filter.type === TYPE_FILTER_AUTHOR) {
            Object.assign(query, {
                authors: [{ id: filter.value.id }],
            });
        }
    });

    return JSON.stringify(query);
};
