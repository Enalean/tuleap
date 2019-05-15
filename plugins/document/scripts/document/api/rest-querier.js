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
    patchUserPreferenciesForFolderInProject,
    patchEmbeddedFile,
    patchWiki,
    patchLink,
    deleteUserPreferenciesForFolderInProject,
    deleteUserPreferenciesForUnderConstructionModal,
    addUserLegacyUIPreferency,
    cancelUpload,
    createNewVersion,
    addNewFile,
    addNewFolder,
    addNewEmpty,
    addNewWiki,
    addNewEmbedded,
    addNewLink,
    deleteFile,
    deleteLink
};

async function getProject(project_id) {
    const response = await get("/api/projects/" + project_id);

    return response.json();
}

async function getItem(id) {
    const response = await get("/api/docman_items/" + id);

    return response.json();
}

async function addNewDocumentType(url, item) {
    const headers = {
        "content-type": "application/json"
    };

    const json_body = {
        ...item
    };
    const body = JSON.stringify(json_body);

    const response = await post(url, { headers, body });

    return response.json();
}

function addNewFile(item, parent_id) {
    return addNewDocumentType("/api/docman_folders/" + parent_id + "/files", item);
}

function addNewEmpty(item, parent_id) {
    return addNewDocumentType("/api/docman_folders/" + parent_id + "/empties", item);
}

function addNewEmbedded(item, parent_id) {
    return addNewDocumentType("/api/docman_folders/" + parent_id + "/embedded_files", item);
}

function addNewWiki(item, parent_id) {
    return addNewDocumentType("/api/docman_folders/" + parent_id + "/wikis", item);
}

function addNewLink(item, parent_id) {
    return addNewDocumentType("/api/docman_folders/" + parent_id + "/links", item);
}

function addNewFolder(item, parent_id) {
    return addNewDocumentType("/api/docman_folders/" + parent_id + "/folders", item);
}

async function createNewVersion(
    item,
    version_title,
    change_log,
    dropped_file,
    should_lock_file,
    approval_table_action
) {
    const response = await patch(`/api/docman_files/${item.id}`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            version_title,
            change_log,
            file_properties: {
                file_name: dropped_file.name,
                file_size: dropped_file.size
            },
            should_lock_file,
            approval_table_action
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

function patchEmbeddedFile(
    item,
    content,
    version_title,
    change_log,
    should_lock_file,
    approval_table_action
) {
    return patch(`/api/docman_embedded_files/${item.id}`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            version_title,
            change_log,
            embedded_properties: {
                content
            },
            should_lock_file,
            approval_table_action
        })
    });
}

function patchWiki(item, page_name, version_title, change_log, should_lock_file) {
    return patch(`/api/docman_wikis/${item.id}`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            wiki_properties: {
                page_name
            },
            should_lock_file
        })
    });
}

function patchLink(
    item,
    link_url,
    version_title,
    change_log,
    should_lock_file,
    approval_table_action
) {
    return patch(`/api/docman_links/${item.id}`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            version_title,
            change_log,
            title: item.title,
            description: item.description,
            link_properties: {
                link_url
            },
            should_lock_file,
            approval_table_action
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

async function addUserLegacyUIPreferency(user_id, project_id) {
    const key = `plugin_docman_display_new_ui_${project_id}`;

    await patch(`/api/users/${user_id}/preferences`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            key,
            value: "0"
        })
    });
}

function cancelUpload(item) {
    return del(item.uploader.url, {
        headers: {
            "Tus-Resumable": "1.0.0"
        }
    });
}

function deleteFile(item) {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_files/${escaped_item_id}`);
}

function deleteLink(item) {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_links/${escaped_item_id}`);
}
