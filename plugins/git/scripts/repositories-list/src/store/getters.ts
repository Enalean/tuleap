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

import { ERROR_TYPE_NO_ERROR, REPOSITORIES_SORTED_BY_PATH } from "../constants";
import {
    filterAFolder,
    checkRepositoryMatchQuery,
    groupRepositoriesByPath,
    sortByLastUpdateDate,
} from "../support/filter";
import type { Folder, FormattedGitLabRepository, Repository, State } from "../type";

export const currentRepositoryList = (
    state: State,
): Array<Repository | FormattedGitLabRepository | Folder> => [
    ...state.repositories_for_owner[state.selected_owner_id],
];

function isGitLabRepository(
    item: Folder | Repository | FormattedGitLabRepository,
): item is FormattedGitLabRepository {
    return "gitlab_data" in item;
}

export const getGitlabRepositoriesIntegrated = (
    state: State,
): Array<FormattedGitLabRepository | Folder | Repository> => {
    return currentRepositoryList(state).filter(
        (repository: Repository | FormattedGitLabRepository | Folder) => {
            return isGitLabRepository(repository);
        },
    );
};

export const areRepositoriesAlreadyLoadedForCurrentOwner = (state: State): boolean => {
    return Object.prototype.hasOwnProperty.call(
        state.repositories_for_owner,
        state.selected_owner_id,
    );
};

export const isCurrentRepositoryListEmpty = (state: State): boolean =>
    areRepositoriesAlreadyLoadedForCurrentOwner(state) && currentRepositoryList(state).length === 0;

export const getFilteredRepositoriesByLastUpdateDate = (
    state: State,
): Array<Repository | FormattedGitLabRepository | Folder> => {
    if (!areRepositoriesAlreadyLoadedForCurrentOwner(state)) {
        return [];
    }

    return sortByLastUpdateDate(currentRepositoryList(state)).filter(
        (repository: FormattedGitLabRepository | Repository | Folder) =>
            checkRepositoryMatchQuery(repository, state.filter),
    );
};

const root_folder: Folder = {
    is_folder: true,
    label: "root",
    children: [],
};

export const getFilteredRepositoriesGroupedByPath = (state: State): Folder => {
    if (!areRepositoriesAlreadyLoadedForCurrentOwner(state)) {
        return root_folder;
    }
    return filterAFolder(groupRepositoriesByPath(currentRepositoryList(state)), state.filter);
};

export const isFolderDisplayMode = (state: State): boolean =>
    state.display_mode === REPOSITORIES_SORTED_BY_PATH;

export const isThereAResultInCurrentFilteredList = (state: State): boolean => {
    if (isFolderDisplayMode(state)) {
        const repository_children = getFilteredRepositoriesGroupedByPath(state).children;
        return repository_children.length > 0;
    }

    return getFilteredRepositoriesByLastUpdateDate(state).length > 0;
};

export const hasError = (state: State): boolean => state.error_message_type !== ERROR_TYPE_NO_ERROR;

export const hasSuccess = (state: State): boolean => state.success_message.length > 0;

export const getSuccessMessage = (state: State): string => state.success_message;

export const isInitialLoadingDoneWithoutError = (state: State): boolean =>
    !state.is_loading_initial && !hasError(state);

export const isLoading = (state: State): boolean =>
    state.is_loading_initial || state.is_loading_next;

export const isFiltering = (state: State): boolean => state.filter.length > 0;

export const isGitlabUsed = (state: State): boolean =>
    state.services_name_used.indexOf("gitlab") !== -1;

export const areExternalUsedServices = (state: State): boolean => {
    return state.services_name_used.length > 0;
};
