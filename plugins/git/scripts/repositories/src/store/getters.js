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

export const currentRepositoryList = (state) => [
    ...state.repositories_for_owner[state.selected_owner_id],
];

export const getGitlabRepositoriesIntegrated = (state, getters) => {
    return getters.currentRepositoryList.filter((repository) => {
        return repository.gitlab_data !== undefined && repository.gitlab_data !== null;
    });
};

export const isCurrentRepositoryListEmpty = (state, getters) =>
    getters.areRepositoriesAlreadyLoadedForCurrentOwner &&
    getters.currentRepositoryList.length === 0;

export const areRepositoriesAlreadyLoadedForCurrentOwner = (state) => {
    return Object.prototype.hasOwnProperty.call(
        state.repositories_for_owner,
        state.selected_owner_id
    );
};

export const getFilteredRepositoriesByLastUpdateDate = (state, getters) => {
    if (!getters.areRepositoriesAlreadyLoadedForCurrentOwner) {
        return [];
    }

    return sortByLastUpdateDate(currentRepositoryList(state)).filter((repository) =>
        checkRepositoryMatchQuery(repository, state.filter)
    );
};

export const getFilteredRepositoriesGroupedByPath = (state, getters) => {
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

export const isThereAResultInCurrentFilteredList = (state, getters) => {
    return getters.isFolderDisplayMode
        ? getters.getFilteredRepositoriesGroupedByPath.children.length > 0
        : getters.getFilteredRepositoriesByLastUpdateDate.length > 0;
};

export const hasError = (state) => state.error_message_type !== ERROR_TYPE_NO_ERROR;

export const hasSuccess = (state) => state.success_message.length > 0;

export const getSuccessMessage = (state) => state.success_message;

export const isInitialLoadingDoneWithoutError = (state, getters) =>
    !state.is_loading_initial && !getters.hasError;

export const isFolderDisplayMode = (state) => state.display_mode === REPOSITORIES_SORTED_BY_PATH;

export const isLoading = (state) => state.is_loading_initial || state.is_loading_next;

export const isFiltering = (state) => state.filter.length > 0;

export const isGitlabUsed = (state) => state.services_name_used.indexOf("gitlab") !== -1;

export const areExternalUsedServices = (state) => state.services_name_used.length > 0;
