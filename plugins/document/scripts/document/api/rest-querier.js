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

import { get, recursiveGet, patch, del, post } from "tlp";
import { DOCMAN_FOLDER_EXPANDED_VALUE } from "../constants.js";

export {
    getProject,
    getFolderContent,
    getItem,
    getParents,
    getUserPreferencesForFolderInProject,
    patchUserPreferenciesForFolderInProject,
    deleteUserPreferenciesForFolderInProject,
    addNewDocument
};

async function getProject(project_id) {
    const response = await get("/api/projects/" + project_id);

    return response.json();
}

async function getItem(id) {
    const response = await get("/api/docman_items/" + id);

    return response.json();
}

async function addNewDocument(item, parent_id) {
    const headers = {
        "content-type": "application/json"
    };

    const body = JSON.stringify({
        ...item,
        parent_id
    });

    const response = await post("/api/docman_items", { headers, body });

    return response.json();
}

function getFolderContent(folder_id) {
    return recursiveGet("/api/docman_items/" + folder_id + "/docman_items", {
        params: {
            limit: 50,
            offset: 0
        }
    });
}

function getParents(folder_id) {
    return recursiveGet("/api/docman_items/" + folder_id + "/parents", {
        params: {
            limit: 50,
            offset: 0
        }
    });
}

async function getUserPreferencesForFolderInProject(user_id, project_id, folder_id) {
    const response = await get(`/api/users/${user_id}/preferences`, {
        params: {
            key: `plugin_docman_hide_${project_id}_${folder_id}`
        }
    });

    return response.json();
}

async function patchUserPreferenciesForFolderInProject(user_id, project_id, folder_id) {
    await patch(`/api/users/${user_id}/preferences`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            key: `plugin_docman_hide_${project_id}_${folder_id}`,
            value: DOCMAN_FOLDER_EXPANDED_VALUE
        })
    });
}

async function deleteUserPreferenciesForFolderInProject(user_id, project_id, folder_id) {
    const key = `plugin_docman_hide_${project_id}_${folder_id}`;

    await del(`/api/users/${user_id}/preferences?key=${key}`);
}
