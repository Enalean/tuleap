/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { getProjectId } from "../../repository-list-presenter";
import {
    formatUrlToGetAllProject,
    formatUrlToGetProjectFromId,
} from "../../gitlab/gitlab-credentials-helper";
import {
    getAsyncGitlabRepositoryList as getAsyncGitlabRepository,
    getGitlabRepositoryList as getGitlabRepository,
    patchGitlabRepository,
    postGitlabRepository,
} from "../../gitlab/gitlab-api-querier";
import { getErrorCode } from "../../support/error-handler";

export const showAddGitlabRepositoryModal = ({ state }) => {
    state.add_gitlab_repository_modal.toggle();
};

export const showDeleteGitlabRepositoryModal = (context, repository) => {
    context.commit("setUnlinkGitlabRepository", repository);
    context.state.unlink_gitlab_repository_modal.toggle();
};

export const showEditAccessTokenGitlabRepositoryModal = (context, repository) => {
    context.commit("setEditAccessTokenGitlabRepository", repository);
    context.state.edit_access_token_gitlab_repository_modal.toggle();
};

export const showRegenerateGitlabWebhookModal = (context, repository) => {
    context.commit("setRegenerateGitlabWebhookRepository", repository);
    context.state.regenerate_gitlab_webhook_modal.toggle();
};

export async function getGitlabRepositories(context, order_by) {
    const getGitlabRepositories = (callback) =>
        getGitlabRepository(getProjectId(), order_by, callback);

    await getAsyncGitlabRepositoryList(context.commit, getGitlabRepositories);
}

export async function getAsyncGitlabRepositoryList(commit, getGitlabRepositories) {
    commit("setIsLoadingInitial", true, { root: true });
    commit("setIsLoadingNext", true, { root: true });
    try {
        return await getGitlabRepositories((repositories) => {
            commit("pushGitlabRepositoriesForCurrentOwner", repositories, { root: true });
            commit("setIsLoadingInitial", false, { root: true });
        });
    } catch (e) {
        commit("setErrorMessageType", getErrorCode(e));
        throw e;
    } finally {
        commit("setIsLoadingNext", false, { root: true });
        commit("setIsFirstLoadDone", true, { root: true });
    }
}

export async function getGitlabProjectList(context, credentials) {
    let pagination = 1;
    let repositories_gitlab = [];
    credentials.server_url = formatUrlToGetAllProject(credentials.server_url);
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

export async function getGitlabRepositoryFromId(context, { credentials, id }) {
    credentials.server_url = formatUrlToGetProjectFromId(credentials.server_url, id);

    const response = await getAsyncGitlabRepository(credentials);

    if (response.status !== 200) {
        throw Error();
    }

    return response.json();
}

async function queryAPIGitlab(credentials, server_url_without_pagination, pagination) {
    credentials.server_url = server_url_without_pagination + "&page=" + pagination;

    const response = await getAsyncGitlabRepository(credentials);
    if (response.status !== 200) {
        throw Error();
    }

    return response.json();
}

export async function postIntegrationGitlab(context, data) {
    const response = await postGitlabRepository(data);

    return response.json();
}

export async function updateBotApiTokenGitlab(
    context,
    { gitlab_bot_api_token, gitlab_repository_id, gitlab_repository_url }
) {
    const body = {
        update_bot_api_token: {
            gitlab_bot_api_token,
            gitlab_repository_id,
            gitlab_repository_url,
        },
    };

    await patchGitlabRepository(body);
}

export async function regenerateGitlabWebhook(
    context,
    { gitlab_repository_id, gitlab_repository_url }
) {
    const body = {
        generate_new_secret: {
            gitlab_repository_id,
            gitlab_repository_url,
        },
    };

    await patchGitlabRepository(body);
}
