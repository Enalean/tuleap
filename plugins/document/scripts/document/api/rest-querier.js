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

import { del, get, patch, post, recursiveGet } from "tlp";
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
    deleteLink,
    deleteEmbeddedFile,
    deleteWiki,
    deleteEmptyDocument,
    deleteFolder,
    getItemsReferencingSameWikiPage,
    postLockFile,
    deleteLockFile,
    postLockEmbedded,
    deleteLockEmbedded,
    postLockWiki,
    deleteLockWiki,
    postLockLink,
    deleteLockLink,
    postLockEmpty,
    deleteLockEmpty,
    setNarrowModeForEmbeddedDisplay,
    removeUserPreferenceForEmbeddedDisplay,
    getPreferenceForEmbeddedDisplay
};

async function getProject(project_id) {
    const response = await get("/api/projects/" + encodeURIComponent(project_id));

    return response.json();
}

async function getItem(id) {
    const response = await get("/api/docman_items/" + encodeURIComponent(id));

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
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/files",
        item
    );
}

function addNewEmpty(item, parent_id) {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/empties",
        item
    );
}

function addNewEmbedded(item, parent_id) {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/embedded_files",
        item
    );
}

function addNewWiki(item, parent_id) {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/wikis",
        item
    );
}

function addNewLink(item, parent_id) {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/links",
        item
    );
}

function addNewFolder(item, parent_id) {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/folders",
        item
    );
}

async function createNewVersion(
    item,
    version_title,
    change_log,
    dropped_file,
    should_lock_file,
    approval_table_action
) {
    const response = await post(`/api/docman_files/${encodeURIComponent(item.id)}/version`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            version_title,
            change_log,
            title: item.title,
            description: item.description,
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
    return recursiveGet("/api/docman_items/" + encodeURIComponent(folder_id) + "/docman_items", {
        params: {
            limit: 50,
            offset: 0
        }
    });
}

function getParents(folder_id) {
    return recursiveGet("/api/docman_items/" + encodeURIComponent(folder_id) + "/parents", {
        params: {
            limit: 50,
            offset: 0
        }
    });
}

async function patchUserPreferenciesForFolderInProject(user_id, project_id, folder_id) {
    await patch(`/api/users/${encodeURIComponent(user_id)}/preferences`, {
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
    return patch(`/api/docman_embedded_files/${encodeURIComponent(item.id)}`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            version_title,
            change_log,
            embedded_properties: {
                content
            },
            title: item.title,
            description: item.description,
            should_lock_file,
            approval_table_action
        })
    });
}

function patchWiki(item, page_name, version_title, change_log, should_lock_file) {
    return patch(`/api/docman_wikis/${encodeURIComponent(item.id)}`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            wiki_properties: {
                page_name
            },
            title: item.title,
            description: item.description,
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
    return patch(`/api/docman_links/${encodeURIComponent(item.id)}`, {
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
    await del(
        `/api/users/${encodeURIComponent(user_id)}/preferences?key=${encodeURIComponent(key)}`
    );
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

    await patch(`/api/users/${encodeURIComponent(user_id)}/preferences`, {
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

function deleteEmbeddedFile(item) {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_embedded_files/${escaped_item_id}`);
}

function deleteWiki(item, { delete_associated_wiki_page = false }) {
    const escaped_item_id = encodeURIComponent(item.id);
    const escaped_option = encodeURIComponent(delete_associated_wiki_page);

    return del(
        `/api/docman_wikis/${escaped_item_id}?delete_associated_wiki_page=${escaped_option}`
    );
}

function deleteFolder(item) {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_folders/${escaped_item_id}`);
}

function deleteEmptyDocument(item) {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_empty_documents/${escaped_item_id}`);
}

async function getItemsReferencingSameWikiPage(page_id) {
    const escaped_page_id = encodeURIComponent(page_id);
    const response = await get(`/api/phpwiki/${escaped_page_id}/items_referencing_wiki_page`);

    return response.json();
}

function postLockFile(item) {
    const headers = {
        "content-type": "application/json"
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_files/${escaped_item_id}/lock`, { headers });
}

function deleteLockFile(item) {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_files/${escaped_item_id}/lock`);
}

function postLockEmbedded(item) {
    const headers = {
        "content-type": "application/json"
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_embedded_files/${escaped_item_id}/lock`, { headers });
}

function deleteLockEmbedded(item) {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_embedded_files/${escaped_item_id}/lock`);
}

function postLockWiki(item) {
    const headers = {
        "content-type": "application/json"
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_wikis/${escaped_item_id}/lock`, { headers });
}

function deleteLockWiki(item) {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_wikis/${escaped_item_id}/lock`);
}

function postLockLink(item) {
    const headers = {
        "content-type": "application/json"
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_links/${escaped_item_id}/lock`, { headers });
}

function deleteLockLink(item) {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_links/${escaped_item_id}/lock`);
}

function postLockEmpty(item) {
    const headers = {
        "content-type": "application/json"
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_empty_documents/${escaped_item_id}/lock`, { headers });
}

function deleteLockEmpty(item) {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_empty_documents/${escaped_item_id}/lock`);
}

async function setNarrowModeForEmbeddedDisplay(user_id, project_id, document_id) {
    await patch(`/api/users/${encodeURIComponent(user_id)}/preferences`, {
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            key: `plugin_docman_display_embedded_${project_id}_${document_id}`,
            value: "narrow"
        })
    });
}

async function removeUserPreferenceForEmbeddedDisplay(user_id, project_id, document_id) {
    const key = `plugin_docman_display_embedded_${project_id}_${document_id}`;

    await del(
        `/api/users/${encodeURIComponent(user_id)}/preferences?key=${encodeURIComponent(key)}`
    );
}

async function getPreferenceForEmbeddedDisplay(user_id, project_id, document_id) {
    const escaped_user_id = encodeURIComponent(user_id);
    const escaped_preference_key = encodeURIComponent(
        `plugin_docman_display_embedded_${project_id}_${document_id}`
    );
    const response = await get(
        `/api/users/${escaped_user_id}/preferences?key=${escaped_preference_key}`
    );

    return response.json();
}
