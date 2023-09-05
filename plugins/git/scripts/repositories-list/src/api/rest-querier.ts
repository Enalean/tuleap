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

import { patch, post, del, recursiveGet } from "@tuleap/tlp-fetch";
import { REPOSITORIES_SORTED_BY_PATH } from "../constants";
import type { Repository } from "../type";

export type RepositoryCallback = (repositories: Repository[]) => void;

const USER_PREFERENCE_KEY = "are_git_repositories_sorted_by_path";

export interface GitRepositoryRecursiveGet {
    repositories: Array<Repository>;
}

export function setRepositoriesSortedByPathUserPreference(user_id: number): Promise<Response> {
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

export function deleteRepositoriesSortedByPathUserPreference(user_id: number): Promise<Response> {
    return del(`/api/users/${user_id}/preferences?key=${USER_PREFERENCE_KEY}`);
}

function buildCollectionCallback(displayCallback: RepositoryCallback) {
    return ({ repositories }: GitRepositoryRecursiveGet): Array<Repository> => {
        displayCallback(repositories);
        return repositories;
    };
}

export function getForkedRepositoryList(
    project_id: number,
    owner_id: string,
    order_by: string,
    displayCallback: RepositoryCallback,
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

export function getRepositoryList(
    project_id: number,
    order_by: string,
    displayCallback: RepositoryCallback,
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

export async function postRepository(
    project_id: number,
    repository_name: string,
): Promise<Repository> {
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
