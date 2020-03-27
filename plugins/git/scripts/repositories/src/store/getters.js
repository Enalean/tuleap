/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { ERROR_TYPE_NO_ERROR, REPOSITORIES_SORTED_BY_PATH } from "../constants.js";

export const currentRepositoryList = (state) =>
    state.repositories_for_owner[state.selected_owner_id];

export const isCurrentRepositoryListEmpty = (state, getters) =>
    getters.areRepositoriesAlreadyLoadedForCurrentOwner &&
    getters.currentRepositoryList.length === 0;

export const areRepositoriesAlreadyLoadedForCurrentOwner = (state) => {
    return Object.prototype.hasOwnProperty.call(
        state.repositories_for_owner,
        state.selected_owner_id
    );
};

export const filteredRepositoriesByLastUpdateDate = (state, getters) => {
    if (!getters.areRepositoriesAlreadyLoadedForCurrentOwner) {
        return [];
    }

    return sortByLastUpdateDate(getters.currentRepositoryList).filter((repository) =>
        filterRepositoriesOnName(repository, state.filter)
    );
};

const filterRepositoriesOnName = (repository, query) =>
    repository.normalized_path.toLowerCase().includes(query.toLowerCase());

const sortByLastUpdateDate = (repositories) =>
    repositories.sort((a, b) => new Date(b.last_update_date) - new Date(a.last_update_date));

export const filteredRepositoriesGroupedByPath = (state, getters) => {
    if (!getters.areRepositoriesAlreadyLoadedForCurrentOwner) {
        return root_folder;
    }

    return filterAFolder(groupRepositoriesByPath(getters.currentRepositoryList), state.filter);
};

const root_folder = {
    is_folder: true,
    label: "root",
    children: [],
};

const filterAFolder = (folder, query) => {
    const filtered_children = folder.children.reduce((accumulator, child) => {
        const filtered_child = filterAChild(child, query);
        if (filtered_child) {
            accumulator.push(filtered_child);
        }
        return accumulator;
    }, []);

    return {
        ...folder,
        children: filtered_children,
    };
};

const filterAChild = (child, query) => {
    if (child.is_folder) {
        const filtered_folder = filterAFolder(child, query);
        if (filtered_folder.children.length === 0) {
            return;
        }
        return filtered_folder;
    }

    if (!filterRepositoriesOnName(child, query)) {
        return;
    }
    return child;
};

const groupRepositoriesByPath = (repositories) => {
    const grouped = repositories.reduce(
        (accumulator, repository) => {
            if (repository.path_without_project) {
                const split_path = repository.path_without_project.split("/");
                const end_of_path = split_path.reduce(createHierarchy, accumulator);

                end_of_path.children.set(repository.label, repository);
                return accumulator;
            }

            accumulator.children.set(repository.label, repository);
            return accumulator;
        },
        {
            is_folder: true,
            label: "root",
            children: new Map(),
        }
    );

    return recursivelySortAlphabetically(grouped);
};

const sortByLabelAlphabetically = (items) =>
    items.sort((a, b) => a.label.localeCompare(b.label, undefined, { numeric: true }));

const recursivelySortAlphabetically = (folder) => {
    let folders = [];
    let repositories = [];
    folder.children.forEach((value) => {
        if (value.is_folder) {
            const sorted_folder = recursivelySortAlphabetically(value);
            folders.push(sorted_folder);
            return;
        }
        repositories.push(value);
    });
    const sorted_children = [
        ...sortByLabelAlphabetically(folders),
        ...sortByLabelAlphabetically(repositories),
    ];

    return {
        ...folder,
        children: sorted_children,
    };
};

const createHierarchy = (hierarchy, path_part) => {
    if (!hierarchy.children.has(path_part)) {
        hierarchy.children.set(path_part, {
            is_folder: true,
            label: path_part,
            children: new Map(),
        });
    }

    return hierarchy.children.get(path_part);
};

export const isThereAResultInCurrentFilteredList = (state, getters) => {
    return getters.isFolderDisplayMode
        ? getters.filteredRepositoriesGroupedByPath.children.length > 0
        : getters.filteredRepositoriesByLastUpdateDate.length > 0;
};

export const hasError = (state) => state.error_message_type !== ERROR_TYPE_NO_ERROR;

export const isInitialLoadingDoneWithoutError = (state, getters) =>
    !state.is_loading_initial && !getters.hasError;

export const isFolderDisplayMode = (state) => state.display_mode === REPOSITORIES_SORTED_BY_PATH;

export const isLoading = (state) => state.is_loading_initial || state.is_loading_next;

export const isFiltering = (state) => state.filter.length > 0;
