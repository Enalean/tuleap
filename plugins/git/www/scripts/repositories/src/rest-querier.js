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

import { post, recursiveGet } from "tlp";

export { getRepositoryList, postRepository };

function getRepositoryList(project_id, displayCallback) {
    return recursiveGet("/api/projects/" + project_id + "/git", {
        params: {
            query: JSON.stringify({
                scope: "project"
            }),
            limit: 50,
            offset: 0
        },
        getCollectionCallback: ({ repositories }) => {
            displayCallback(repositories);
            return repositories;
        }
    });
}

async function postRepository(project_id, repository_name) {
    const headers = {
        "content-type": "application/json"
    };

    const body = JSON.stringify({
        project_id,
        name: repository_name
    });

    const response = await post("/api/git/", {
        headers,
        body
    });

    return response.json();
}
