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
import {
    DOCMAN_FOLDER_EXPANDED_VALUE,
    TYPE_LINK,
    TYPE_WIKI,
    TYPE_FILE,
    TYPE_EMBEDDED
} from "../constants.js";

export {
    getProject,
    getFolderContent,
    getItem,
    getParents,
    patchUserPreferenciesForFolderInProject,
    deleteUserPreferenciesForFolderInProject,
    deleteUserPreferenciesForUnderConstructionModal,
    addNewDocument,
    deleteUserPreferenciesForUIInProject,
    cancelUpload,
    createNewVersion,
    addNewFile,
    addNewFolder,
    addNewEmpty
};

async function getProject(project_id) {
    const response = await get("/api/projects/" + project_id);

    return response.json();
}

async function getItem(id) {
    const response = await get("/api/docman_items/" + id);

    return response.json();
}

async function addNewFile(item, parent_id) {
    const headers = {
        "content-type": "application/json"
    };

    const json_body = {
        ...item
    };
    const body = JSON.stringify(json_body);

    const response = await post("/api/docman_folders/" + parent_id + "/files", { headers, body });

    return response.json();
}

async function addNewEmpty(item, parent_id) {
    const headers = {
        "content-type": "application/json"
    };

    const json_body = {
        ...item
    };
    const body = JSON.stringify(json_body);

    const response = await post("/api/docman_folders/" + parent_id + "/empties", { headers, body });

    return response.json();
}

async function addNewFolder(item, parent_id) {
    const headers = {
        "content-type": "application/json"
    };

    const json_body = {
        ...item
    };
    const body = JSON.stringify(json_body);

    const response = await post("/api/docman_folders/" + parent_id + "/folders", { headers, body });

    return response.json();
}

async function addNewDocument(item, parent_id) {
    const headers = {
        "content-type": "application/json"
    };

    const json_body = {
        ...item,
        parent_id
    };
    cleanUpBody(json_body);
    const body = JSON.stringify(json_body);

    const response = await post("/api/docman_items", { headers, body });

    return response.json();

    function cleanUpBody(item) {
        const properties_to_remove = {
            wiki_properties: 1,
            link_properties: 1,
            file_properties: 1,
            embedded_properties: 1
        };
        const properties_to_keep_by_type = {};
        properties_to_keep_by_type[TYPE_LINK] = "link_properties";
        properties_to_keep_by_type[TYPE_WIKI] = "wiki_properties";
        properties_to_keep_by_type[TYPE_FILE] = "file_properties";
        properties_to_keep_by_type[TYPE_EMBEDDED] = "embedded_properties";

        if (properties_to_keep_by_type.hasOwnProperty(item.type)) {
            delete properties_to_remove[properties_to_keep_by_type[item.type]];
        }

        Object.keys(properties_to_remove).forEach(property => {
            if (item.hasOwnProperty(property)) {
                delete item[property];
            }
        });
    }
}

async function createNewVersion(item, version_title, changelog, dropped_file) {
    const response = await patch(`/api/docman_files/${item.id}`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            version_title,
            changelog,
            file_properties: {
                file_name: dropped_file.name,
                file_size: dropped_file.size
            }
        })
    });

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

async function deleteUserPreference(user_id, key) {
    await del(`/api/users/${user_id}/preferences?key=${key}`);
}

async function deleteUserPreferenciesForFolderInProject(user_id, project_id, folder_id) {
    const key = `plugin_docman_hide_${project_id}_${folder_id}`;

    await deleteUserPreference(user_id, key);
}

async function deleteUserPreferenciesForUnderConstructionModal(user_id, project_id) {
    const key = `plugin_document_set_display_under_construction_modal_${project_id}`;

    await deleteUserPreference(user_id, key);
}

async function deleteUserPreferenciesForUIInProject(user_id, project_id) {
    const key = `plugin_docman_display_new_ui_${project_id}`;

    await deleteUserPreference(user_id, key);
}

function cancelUpload(item) {
    return del(item.uploader.url, {
        headers: {
            "Tus-Resumable": "1.0.0"
        }
    });
}
