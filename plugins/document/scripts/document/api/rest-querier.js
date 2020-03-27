/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { del, get, patch, post, put, recursiveGet } from "tlp";
import { DOCMAN_FOLDER_EXPANDED_VALUE } from "../constants.js";

export {
    getDocumentManagerServiceInformation,
    getFolderContent,
    getItem,
    getParents,
    patchUserPreferenciesForFolderInProject,
    postEmbeddedFile,
    postWiki,
    postLinkVersion,
    deleteUserPreferenciesForFolderInProject,
    addUserLegacyUIPreferency,
    cancelUpload,
    createNewVersion,
    addNewFile,
    addNewFolder,
    addNewEmpty,
    addNewWiki,
    addNewEmbedded,
    addNewLink,
    moveFile,
    moveFolder,
    moveEmpty,
    moveWiki,
    moveEmbedded,
    moveLink,
    copyFile,
    copyFolder,
    copyEmpty,
    copyWiki,
    copyEmbedded,
    copyLink,
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
    getPreferenceForEmbeddedDisplay,
    putFileMetadata,
    putEmbeddedFileMetadata,
    putLinkMetadata,
    putWikiMetadata,
    putEmptyDocumentMetadata,
    putFolderDocumentMetadata,
    putEmbeddedFilePermissions,
    putFilePermissions,
    putLinkPermissions,
    putWikiPermissions,
    putEmptyDocumentPermissions,
    putFolderPermissions,
    getProjectUserGroups,
    getProjectMetadata,
    postNewLinkVersionFromEmpty,
    postNewEmbeddedFileVersionFromEmpty,
    postNewFileVersionFromEmpty,
};

async function getDocumentManagerServiceInformation(project_id) {
    const response = await get(
        "/api/projects/" + encodeURIComponent(project_id) + "/docman_service"
    );

    return response.json();
}

async function getItem(id) {
    const response = await get("/api/docman_items/" + encodeURIComponent(id));

    return response.json();
}

async function addNewDocumentType(url, item) {
    const headers = {
        "content-type": "application/json",
    };

    const json_body = {
        ...item,
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

async function moveDocumentType(url, destination_folder_id) {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify({
        move: {
            destination_folder_id: destination_folder_id,
        },
    });

    await patch(url, { headers, body });
}

function moveFile(moved_item_id, parent_id) {
    return moveDocumentType("/api/docman_files/" + encodeURIComponent(moved_item_id), parent_id);
}

function moveEmpty(moved_item_id, parent_id) {
    return moveDocumentType(
        "/api/docman_empty_documents/" + encodeURIComponent(moved_item_id),
        parent_id
    );
}

function moveEmbedded(moved_item_id, parent_id) {
    return moveDocumentType(
        "/api/docman_embedded_files/" + encodeURIComponent(moved_item_id),
        parent_id
    );
}

function moveWiki(moved_item_id, parent_id) {
    return moveDocumentType("/api/docman_wikis/" + encodeURIComponent(moved_item_id), parent_id);
}

function moveLink(moved_item_id, parent_id) {
    return moveDocumentType("/api/docman_links/" + encodeURIComponent(moved_item_id), parent_id);
}

function moveFolder(moved_item_id, parent_id) {
    return moveDocumentType("/api/docman_folders/" + encodeURIComponent(moved_item_id), parent_id);
}

async function copyDocumentType(url, copied_item_id) {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify({
        copy: {
            item_id: copied_item_id,
        },
    });

    const response = await post(url, { headers, body });

    return response.json();
}

function copyFile(copied_item_id, parent_id) {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/files",
        copied_item_id
    );
}

function copyEmpty(copied_item_id, parent_id) {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/empties",
        copied_item_id
    );
}

function copyEmbedded(copied_item_id, parent_id) {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/embedded_files",
        copied_item_id
    );
}

function copyWiki(copied_item_id, parent_id) {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/wikis",
        copied_item_id
    );
}

function copyLink(copied_item_id, parent_id) {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/links",
        copied_item_id
    );
}

function copyFolder(copied_item_id, parent_id) {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/folders",
        copied_item_id
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
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            version_title,
            change_log,
            title: item.title,
            description: item.description,
            file_properties: {
                file_name: dropped_file.name,
                file_size: dropped_file.size,
            },
            should_lock_file,
            approval_table_action,
        }),
    });

    return response.json();
}

function getFolderContent(folder_id) {
    return recursiveGet("/api/docman_items/" + encodeURIComponent(folder_id) + "/docman_items", {
        params: {
            limit: 50,
            offset: 0,
        },
    });
}

function getParents(folder_id) {
    return recursiveGet("/api/docman_items/" + encodeURIComponent(folder_id) + "/parents", {
        params: {
            limit: 50,
            offset: 0,
        },
    });
}

async function patchUserPreferenciesForFolderInProject(user_id, project_id, folder_id) {
    await patch(`/api/users/${encodeURIComponent(user_id)}/preferences`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            key: `plugin_docman_hide_${project_id}_${folder_id}`,
            value: DOCMAN_FOLDER_EXPANDED_VALUE,
        }),
    });
}

function postEmbeddedFile(
    item,
    content,
    version_title,
    change_log,
    should_lock_file,
    approval_table_action
) {
    return post(`/api/docman_embedded_files/${encodeURIComponent(item.id)}/version`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            version_title,
            change_log,
            embedded_properties: {
                content,
            },
            should_lock_file,
            approval_table_action,
        }),
    });
}

function postWiki(item, page_name, version_title, change_log, should_lock_file) {
    return post(`/api/docman_wikis/${encodeURIComponent(item.id)}/version`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            wiki_properties: {
                page_name,
            },
            should_lock_file,
        }),
    });
}

function postLinkVersion(
    item,
    link_url,
    version_title,
    change_log,
    should_lock_file,
    approval_table_action
) {
    return post(`/api/docman_links/${encodeURIComponent(item.id)}/version`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            version_title,
            change_log,
            link_properties: {
                link_url,
            },
            should_lock_file,
            approval_table_action,
        }),
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

async function addUserLegacyUIPreferency(user_id, project_id) {
    const key = `plugin_docman_display_new_ui_${project_id}`;

    await patch(`/api/users/${encodeURIComponent(user_id)}/preferences`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            key,
            value: "0",
        }),
    });
}

function cancelUpload(item) {
    return del(item.uploader.url, {
        headers: {
            "Tus-Resumable": "1.0.0",
        },
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
        "content-type": "application/json",
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
        "content-type": "application/json",
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
        "content-type": "application/json",
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
        "content-type": "application/json",
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
        "content-type": "application/json",
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
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            key: `plugin_docman_display_embedded_${project_id}_${document_id}`,
            value: "narrow",
        }),
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

function putFileMetadata(id, title, description, owner_id, status, obsolescence_date, metadata) {
    return put(`/api/docman_files/${encodeURIComponent(id)}/metadata`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            metadata,
        }),
    });
}

function putEmbeddedFileMetadata(
    id,
    title,
    description,
    owner_id,
    status,
    obsolescence_date,
    metadata
) {
    return put(`/api/docman_embedded_files/${encodeURIComponent(id)}/metadata`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            metadata,
        }),
    });
}

function putLinkMetadata(id, title, description, owner_id, status, obsolescence_date, metadata) {
    return put(`/api/docman_links/${encodeURIComponent(id)}/metadata`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            metadata,
        }),
    });
}

function putWikiMetadata(id, title, description, owner_id, status, obsolescence_date, metadata) {
    return put(`/api/docman_wikis/${encodeURIComponent(id)}/metadata`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            metadata,
        }),
    });
}

function putEmptyDocumentMetadata(
    id,
    title,
    description,
    owner_id,
    status,
    obsolescence_date,
    metadata
) {
    return put(`/api/docman_empty_documents/${encodeURIComponent(id)}/metadata`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            metadata,
        }),
    });
}

function putFolderDocumentMetadata(
    id,
    title,
    description,
    owner_id,
    status,
    obsolescence_date,
    metadata
) {
    return put(`/api/docman_folders/${encodeURIComponent(id)}/metadata`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            metadata,
        }),
    });
}

function putEmbeddedFilePermissions(id, permissions) {
    return put(`/api/docman_embedded_files/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

function putFilePermissions(id, permissions) {
    return put(`/api/docman_files/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

function putLinkPermissions(id, permissions) {
    return put(`/api/docman_links/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

function putWikiPermissions(id, permissions) {
    return put(`/api/docman_wikis/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

function putEmptyDocumentPermissions(id, permissions) {
    return put(`/api/docman_empty_documents/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

function putFolderPermissions(id, permissions) {
    return put(`/api/docman_folders/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

async function getProjectUserGroups(project_id) {
    const response = await get(
        "/api/projects/" +
            encodeURIComponent(project_id) +
            "/user_groups?query=" +
            encodeURIComponent(JSON.stringify({ with_system_user_groups: true }))
    );

    return response.json();
}

function getProjectMetadata(project_id) {
    const escaped_project_id = encodeURIComponent(project_id);
    return recursiveGet(`/api/projects/${escaped_project_id}/docman_metadata`, {
        params: {
            limit: 50,
            offset: 0,
        },
    });
}

function postNewLinkVersionFromEmpty(item_id, link_url) {
    const escaped_item_id = encodeURIComponent(item_id);
    return post(`/api/docman_empty_documents/${escaped_item_id}/link`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            link_url,
        }),
    });
}

function postNewEmbeddedFileVersionFromEmpty(item_id, content) {
    const escaped_item_id = encodeURIComponent(item_id);
    return post(`/api/docman_empty_documents/${escaped_item_id}/embedded_file`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            content,
        }),
    });
}

async function postNewFileVersionFromEmpty(item_id, dropped_file) {
    const response = await post(`/api/docman_empty_documents/${encodeURIComponent(item_id)}/file`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            file_name: dropped_file.name,
            file_size: dropped_file.size,
        }),
    });

    return response.json();
}
