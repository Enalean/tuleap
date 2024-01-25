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

import type { Ref } from "vue";
import type { PullRequestsListFilter, PullRequestsListFilterType } from "./PullRequestsListFilter";

export type StoreListFilters = {
    storeFilter(filter: PullRequestsListFilter): void;
    deleteFilter(filter: PullRequestsListFilter): void;
    clearAllFilters(): void;
    getFilters(): Ref<PullRequestsListFilter[]>;
    hasAFilterWithType(type: PullRequestsListFilterType): boolean;
};

type FindFilter = (filter: PullRequestsListFilter) => boolean;

const withSameType =
    (new_filter: PullRequestsListFilter): FindFilter =>
    (filter: PullRequestsListFilter) =>
        filter.type === new_filter.type;
const withSameId =
    (filter_to_delete: PullRequestsListFilter): FindFilter =>
    (filter: PullRequestsListFilter) =>
        filter.id === filter_to_delete.id;

export const ListFiltersStore = (filters: Ref<PullRequestsListFilter[]>): StoreListFilters => ({
    getFilters: () => filters,
    storeFilter: (filter: PullRequestsListFilter): void => {
        const index = filters.value.findIndex(withSameType(filter));
        if (index !== -1) {
            filters.value.splice(index, 1, filter);
            return;
        }

        filters.value.push(filter);
    },
    deleteFilter: (filter: PullRequestsListFilter): void => {
        const index = filters.value.findIndex(withSameId(filter));
        if (index === -1) {
            return;
        }

        filters.value.splice(index, 1);
    },
    clearAllFilters: (): void => {
        filters.value.splice(0, filters.value.length);
    },
    hasAFilterWithType: (type: PullRequestsListFilterType): boolean => {
        return filters.value.some((filter) => filter.type === type);
    },
});
