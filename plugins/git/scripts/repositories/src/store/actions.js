/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import {
    getForkedRepositoryList,
    getRepositoryList,
    setRepositoriesSortedByPathUserPreference,
    deleteRepositoriesSortedByPathUserPreference,
    getGitlabRepositoryList as getGitlabRepository,
} from "../api/rest-querier.js";
import { getProjectId, getUserId } from "../repository-list-presenter.js";
import {
    ERROR_TYPE_UNKNOWN_ERROR,
    ERROR_TYPE_NO_GIT,
    PROJECT_KEY,
    REPOSITORIES_SORTED_BY_PATH,
    ANONYMOUS_USER_ID,
} from "../constants.js";
import { formatUrl } from "../gitlab/gitlab-credentials-helper";
import { getAsyncGitlabRepositoryList as getAsyncGitlabRepository } from "../gitlab/gitlab-api-querier";

export const setDisplayMode = async (context, new_mode) => {
    context.commit("setDisplayMode", new_mode);

    const user_id = getUserId();

    if (!user_id || user_id === ANONYMOUS_USER_ID) {
        return;
    }

    if (new_mode === REPOSITORIES_SORTED_BY_PATH) {
        await setRepositoriesSortedByPathUserPreference(user_id);
    } else {
        await deleteRepositoriesSortedByPathUserPreference(user_id);
    }
};

export const showAddRepositoryModal = ({ state }) => {
    state.add_repository_modal.toggle();
};

export const showAddGitlabRepositoryModal = ({ state }) => {
    state.add_gitlab_repository_modal.toggle();
};

export const showDeleteGitlabRepositoryModal = (context, repository) => {
    context.commit("setUnlinkGitlabRepository", repository);
    context.state.unlink_gitlab_repository_modal.toggle();
};

export const changeRepositories = async (context, new_owner_id) => {
    context.commit("setSelectedOwnerId", new_owner_id);
    context.commit("setFilter", "");

    if (context.getters.areRepositoriesAlreadyLoadedForCurrentOwner) {
        return;
    }

    const order_by = context.getters.isFolderDisplayMode ? "path" : "push_date";
    if (new_owner_id === PROJECT_KEY) {
        const getProjectRepositories = (callback) =>
            getRepositoryList(getProjectId(), order_by, callback);
        await getAsyncRepositoryList(context.commit, getProjectRepositories);

        if (context.getters.isGitlabUsed) {
            await getGitlabRepositories(context, order_by);
        }
    } else {
        const getForkedRepositories = (callback) =>
            getForkedRepositoryList(
                getProjectId(),
                context.state.selected_owner_id,
                order_by,
                callback
            );
        await getAsyncRepositoryList(context.commit, getForkedRepositories);
    }
};

export async function getAsyncRepositoryList(commit, getRepositories) {
    commit("setIsLoadingInitial", true);
    commit("setIsLoadingNext", true);
    try {
        return await getRepositories((repositories) => {
            commit("pushRepositoriesForCurrentOwner", repositories);
            commit("setIsLoadingInitial", false);
        });
    } catch (e) {
        return handleGetRepositoryListError(e, commit);
    } finally {
        commit("setIsLoadingNext", false);
        commit("setIsFirstLoadDone", true);
    }
}

async function getGitlabRepositories(context, order_by) {
    const getGitlabRepositories = (callback) =>
        getGitlabRepository(getProjectId(), order_by, callback);

    await getAsyncGitlabRepositoryList(context.commit, getGitlabRepositories);
}

export async function getAsyncGitlabRepositoryList(commit, getGitlabRepositories) {
    commit("setIsLoadingInitial", true);
    commit("setIsLoadingNext", true);
    try {
        return await getGitlabRepositories((repositories) => {
            commit("pushGitlabRepositoriesForCurrentOwner", repositories);
            commit("setIsLoadingInitial", false);
        });
    } catch (e) {
        return handleGetRepositoryListError(e, commit);
    } finally {
        commit("setIsLoadingNext", false);
        commit("setIsFirstLoadDone", true);
    }
}

async function handleGetRepositoryListError(e, commit) {
    let error_code;

    if (!e.response) {
        throw e;
    }

    try {
        const { error } = await e.response.json();
        error_code = Number.parseInt(error.code, 10);
    } catch (e) {
        commit("setErrorMessageType", ERROR_TYPE_UNKNOWN_ERROR);
        throw e;
    }

    if (error_code === 404) {
        commit("setErrorMessageType", ERROR_TYPE_NO_GIT);
    } else {
        commit("setErrorMessageType", ERROR_TYPE_UNKNOWN_ERROR);
        throw e;
    }
}

export async function getGitlabRepositoryList(context, credentials) {
    let pagination = 1;
    let repositories_gitlab = [];
    credentials.server_url = formatUrl(credentials.server_url);
    const server_url_without_pagination = credentials.server_url;

    const response = await getAsyncGitlabRepository(credentials);

    if (response.status !== 200) {
        throw Error();
    }
    const total_page = response.headers.get("X-Total-Pages");
    repositories_gitlab.push(...(await response.json()));

    pagination++;

    while (pagination <= total_page) {
        const repositories = await queryAPIGitlab(
            credentials,
            server_url_without_pagination,
            pagination
        );
        repositories_gitlab.push(...repositories);
        pagination++;
    }

    return repositories_gitlab;
}

async function queryAPIGitlab(credentials, server_url_without_pagination, pagination) {
    credentials.server_url = server_url_without_pagination + "&page=" + pagination;

    const response = await getAsyncGitlabRepository(credentials);
    if (response.status !== 200) {
        throw Error();
    }

    return response.json();
}
