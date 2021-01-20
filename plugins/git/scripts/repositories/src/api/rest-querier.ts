/*
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

import { patch, post, del, recursiveGet } from "tlp";
import { REPOSITORIES_SORTED_BY_PATH } from "../constants";
import { GitLabData, GitLabDataWithToken, Repository } from "../type";

export type RepositoryCallback = (repositories: Repository[]) => void;

export {
    getRepositoryList,
    getForkedRepositoryList,
    postRepository,
    setRepositoriesSortedByPathUserPreference,
    deleteRepositoriesSortedByPathUserPreference,
    getGitlabRepositoryList,
    deleteIntegrationGitlab,
    postGitlabRepository,
    patchGitlabRepository,
};

const USER_PREFERENCE_KEY = "are_git_repositories_sorted_by_path";

export interface GitLabRepositoryDeletion {
    repository_id: number;
    project_id: number;
}

export interface GitLabRepositoryCreation {
    project_id: number;
    gitlab_repository_id: number;
    gitlab_server_url: string;
    gitlab_bot_api_token: string;
}

export interface GitLabRepositoryUpdate {
    update_bot_api_token?: GitLabDataWithToken;
    generate_new_secret?: GitLabData;
}

export interface GitRepositoryRecursiveGet {
    repositories: Array<Repository>;
}

function setRepositoriesSortedByPathUserPreference(user_id: number): Promise<Response> {
    return patch(`/api/users/${user_id}/preferences`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            key: USER_PREFERENCE_KEY,
            value: REPOSITORIES_SORTED_BY_PATH,
        }),
    });
}

function deleteRepositoriesSortedByPathUserPreference(user_id: number): Promise<Response> {
    return del(`/api/users/${user_id}/preferences?key=${USER_PREFERENCE_KEY}`);
}

function deleteIntegrationGitlab(repository_deletion: GitLabRepositoryDeletion): Promise<Response> {
    return del(
        "/api/v1/gitlab_repositories/" +
            encodeURIComponent(repository_deletion.repository_id) +
            "?project_id=" +
            encodeURIComponent(repository_deletion.project_id)
    );
}

function buildCollectionCallback(displayCallback: RepositoryCallback) {
    return ({ repositories }: GitRepositoryRecursiveGet): Array<Repository> => {
        displayCallback(repositories);
        return repositories;
    };
}

function buildGitlabCollectionCallback(displayCallback: RepositoryCallback) {
    return (repositories: Array<Repository>): Array<Repository> => {
        displayCallback(repositories);
        return repositories;
    };
}

function getForkedRepositoryList(
    project_id: number,
    owner_id: string,
    order_by: string,
    displayCallback: RepositoryCallback
): Promise<Array<Repository>> {
    return recursiveGet("/api/projects/" + project_id + "/git", {
        params: {
            query: JSON.stringify({
                scope: "individual",
                owner_id: Number.parseInt(owner_id, 10),
            }),
            order_by,
            limit: 50,
            offset: 0,
        },
        getCollectionCallback: buildCollectionCallback(displayCallback),
    });
}

function getRepositoryList(
    project_id: number,
    order_by: string,
    displayCallback: RepositoryCallback
): Promise<Array<Repository>> {
    return recursiveGet("/api/projects/" + project_id + "/git", {
        params: {
            query: JSON.stringify({
                scope: "project",
            }),
            order_by,
            limit: 50,
            offset: 0,
        },
        getCollectionCallback: buildCollectionCallback(displayCallback),
    });
}

function getGitlabRepositoryList(
    project_id: number,
    order_by: string,
    displayCallback: RepositoryCallback
): Promise<Array<Repository>> {
    return recursiveGet("/api/projects/" + project_id + "/gitlab_repositories", {
        params: {
            query: JSON.stringify({
                scope: "project",
            }),
            order_by,
            limit: 50,
            offset: 0,
        },
        getCollectionCallback: buildGitlabCollectionCallback(displayCallback),
    });
}

async function postRepository(project_id: number, repository_name: string): Promise<string> {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify({
        project_id,
        name: repository_name,
    });

    const response = await post("/api/git/", {
        headers,
        body,
    });

    return response.json();
}

function postGitlabRepository(repository: GitLabRepositoryCreation): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify(repository);

    return post("/api/gitlab_repositories", {
        headers,
        body,
    });
}

function patchGitlabRepository(body: GitLabRepositoryUpdate): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    return patch("/api/gitlab_repositories", {
        headers,
        body: JSON.stringify(body),
    });
}
