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
import type {
    GitLabRepositoryCreation,
    GitLabRepositoryUpdate,
} from "../../gitlab/gitlab-api-querier";
import {
    getAsyncGitlabRepositoryList as getAsyncGitlabRepository,
    getGitlabRepositoryList as getGitlabRepository,
    patchGitlabRepository,
    postGitlabRepository,
} from "../../gitlab/gitlab-api-querier";
import { getErrorCode } from "../../support/error-handler";
import type { GitlabState } from "./state";
import type { ActionContext } from "vuex";
import type {
    GitLabCredentials,
    GitLabDataWithTokenPayload,
    GitLabRepository,
    Repository,
    State,
} from "../../type";
import {
    formatUrlToGetAllProject,
    formatUrlToGetProjectFromId,
} from "../../gitlab/gitlab-credentials-helper";

export type GitlabRepositoryCallback = (repositories: Repository[]) => void;

export const showDeleteGitlabRepositoryModal = (
    context: ActionContext<GitlabState, State>,
    repository: GitLabRepository,
): void => {
    context.commit("setUnlinkGitlabRepository", repository);
    if (!context.state.unlink_gitlab_repository_modal) {
        return;
    }
    context.state.unlink_gitlab_repository_modal.toggle();
};

export const showEditAccessTokenGitlabRepositoryModal = (
    context: ActionContext<GitlabState, State>,
    repository: GitLabRepository,
): void => {
    context.commit("setEditAccessTokenGitlabRepository", repository);
    if (!context.state.edit_access_token_gitlab_repository_modal) {
        return;
    }
    context.state.edit_access_token_gitlab_repository_modal.toggle();
};

export const showRegenerateGitlabWebhookModal = (
    context: ActionContext<GitlabState, State>,
    repository: GitLabRepository,
): void => {
    context.commit("setRegenerateGitlabWebhookRepository", repository);
    if (!context.state.regenerate_gitlab_webhook_modal) {
        return;
    }
    context.state.regenerate_gitlab_webhook_modal.toggle();
};

export const showArtifactClosureModal = (
    context: ActionContext<GitlabState, State>,
    repository: GitLabRepository,
): void => {
    context.commit("setArtifactClosureRepository", repository);
    if (!context.state.artifact_closure_modal) {
        return;
    }
    context.state.artifact_closure_modal.toggle();
};

export const showCreateBranchPrefixModal = (
    context: ActionContext<GitlabState, State>,
    repository: GitLabRepository,
): void => {
    context.commit("setCreateBranchPrefixRepository", repository);
    if (!context.state.create_branch_prefix_modal) {
        return;
    }
    context.state.create_branch_prefix_modal.toggle();
};

export async function getGitlabRepositories(
    context: ActionContext<GitlabState, State>,
    order_by: string,
): Promise<Array<Repository>> {
    const getGitlabRepositories = (
        callback: GitlabRepositoryCallback,
    ): Promise<Array<Repository>> => getGitlabRepository(getProjectId(), order_by, callback);

    context.commit("setIsLoadingInitial", true, { root: true });
    context.commit("setIsLoadingNext", true, { root: true });
    try {
        return await getGitlabRepositories((repositories) => {
            context.commit("pushGitlabRepositoriesForCurrentOwner", repositories, { root: true });
            context.commit("setIsLoadingInitial", false, { root: true });
        });
    } catch (e) {
        context.commit("setErrorMessageType", getErrorCode(e));
        throw e;
    } finally {
        context.commit("setIsLoadingNext", false, { root: true });
        context.commit("setIsFirstLoadDone", true, { root: true });
    }
}

export async function getGitlabProjectList(
    context: ActionContext<GitlabState, State>,
    credentials: GitLabCredentials,
): Promise<Array<GitLabRepository>> {
    let pagination = 1;
    const repositories_gitlab: Array<GitLabRepository> = [];
    credentials.server_url = formatUrlToGetAllProject(credentials.server_url);
    const server_url_without_pagination = credentials.server_url;

    const response = await getAsyncGitlabRepository(credentials);

    if (response.status !== 200) {
        throw Error();
    }
    const total_pages = response.headers.get("X-Total-Pages");
    if (!total_pages) {
        throw Error("Missing header X-Total-Pages");
    }
    const total_page = parseInt(total_pages, 10);
    repositories_gitlab.push(...(await response.json()));

    pagination++;

    while (pagination <= total_page) {
        const repositories = await queryAPIGitlab(
            credentials,
            server_url_without_pagination,
            pagination,
        );
        repositories_gitlab.push(...repositories);
        pagination++;
    }

    return repositories_gitlab;
}

interface GitLabRepositoryPayload {
    credentials: GitLabCredentials;
    id: number;
}

export async function getGitlabRepositoryFromId(
    context: ActionContext<GitlabState, State>,
    payload: GitLabRepositoryPayload,
): Promise<Response> {
    payload.credentials.server_url = formatUrlToGetProjectFromId(
        payload.credentials.server_url,
        payload.id,
    );

    const response = await getAsyncGitlabRepository(payload.credentials);

    if (response.status !== 200) {
        throw Error();
    }

    return response.json();
}

async function queryAPIGitlab(
    credentials: GitLabCredentials,
    server_url_without_pagination: string,
    pagination: number,
): Promise<Array<GitLabRepository>> {
    credentials.server_url = server_url_without_pagination + "&page=" + pagination;

    const response = await getAsyncGitlabRepository(credentials);
    if (response.status !== 200) {
        throw Error();
    }

    return response.json();
}

export async function postIntegrationGitlab(
    context: ActionContext<GitlabState, State>,
    data: GitLabRepositoryCreation,
): Promise<Response> {
    const response = await postGitlabRepository(data);

    return response.json();
}

export async function updateBotApiTokenGitlab(
    context: ActionContext<GitlabState, State>,
    payload: GitLabDataWithTokenPayload,
): Promise<void> {
    const body: GitLabRepositoryUpdate = {
        update_bot_api_token: {
            gitlab_api_token: payload.gitlab_api_token,
        },
    };

    await patchGitlabRepository(payload.gitlab_integration_id, body);
}

export async function regenerateGitlabWebhook(
    context: ActionContext<GitlabState, State>,
    integration_id: number | string,
): Promise<void> {
    const body: GitLabRepositoryUpdate = {
        generate_new_secret: true,
    };

    await patchGitlabRepository(integration_id, body);
}

interface UpdateGitlabIntegrationArtifactClosurePayload {
    integration_id: number;
    allow_artifact_closure: boolean;
}

interface UpdateGitlabIntegrationCreateBranchPrefixPayload {
    integration_id: number;
    create_branch_prefix: string;
}

export async function updateGitlabRepositoryArtifactClosure(
    context: ActionContext<GitlabState, State>,
    payload: UpdateGitlabIntegrationArtifactClosurePayload,
): Promise<GitLabRepository> {
    const gitlab_repository_update: GitLabRepositoryUpdate = {
        allow_artifact_closure: payload.allow_artifact_closure,
    };
    const response = await patchGitlabRepository(payload.integration_id, gitlab_repository_update);

    const repositories = context.rootGetters.getGitlabRepositoriesIntegrated;
    const concerned_repository_index = repositories.findIndex(
        (repository: Repository) => repository.integration_id === payload.integration_id,
    );

    const repo = repositories[concerned_repository_index];
    repo.allow_artifact_closure = payload.allow_artifact_closure;
    repositories[concerned_repository_index] = repo;

    return response.json();
}

export async function updateGitlabRepositoryCreateBranchPrefix(
    context: ActionContext<GitlabState, State>,
    payload: UpdateGitlabIntegrationCreateBranchPrefixPayload,
): Promise<GitLabRepository> {
    const gitlab_repository_update: GitLabRepositoryUpdate = {
        create_branch_prefix: payload.create_branch_prefix,
    };
    const response = await patchGitlabRepository(payload.integration_id, gitlab_repository_update);

    const repositories = context.rootGetters.getGitlabRepositoriesIntegrated;
    const concerned_repository_index = repositories.findIndex(
        (repository: Repository) => repository.integration_id === payload.integration_id,
    );

    const repo = repositories[concerned_repository_index];
    repo.create_branch_prefix = payload.create_branch_prefix;
    repositories[concerned_repository_index] = repo;

    return response.json();
}
