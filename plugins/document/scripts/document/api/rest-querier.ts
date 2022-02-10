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

import { del, get, post, recursiveGet } from "tlp";
import type {
    AdminPermissions,
    ApprovalTable,
    Embedded,
    Empty,
    Folder,
    Item,
    ItemFile,
    Link,
    Wiki,
    ItemFileUploader,
    UserGroup,
    SearchResult,
    AdvancedSearchParams,
    CreatedItem,
    CreatedItemFileProperties,
} from "../type";
import { SEARCH_LIMIT } from "../type";

export {
    getDocumentManagerServiceInformation,
    getFolderContent,
    getItem,
    getParents,
    postEmbeddedFile,
    postWiki,
    postLinkVersion,
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
    getProjectUserGroups,
    postNewLinkVersionFromEmpty,
    postNewEmbeddedFileVersionFromEmpty,
    postNewFileVersionFromEmpty,
    getItemWithSize,
    searchInFolder,
};

export interface ProjectService {
    permissions_for_groups: AdminPermissions;
    root_item: Folder;
}

interface DeleteWikiPageOptions {
    delete_associated_wiki_page: boolean;
}

async function getDocumentManagerServiceInformation(project_id: number): Promise<ProjectService> {
    const response = await get(
        "/api/projects/" + encodeURIComponent(project_id) + "/docman_service"
    );

    return response.json();
}

async function getItem(id: number): Promise<Item> {
    const response = await get("/api/docman_items/" + encodeURIComponent(id));

    return response.json();
}

async function addNewDocumentType(url: string, item: Item): Promise<CreatedItem> {
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

async function searchInFolder(
    folder_id: number,
    search: AdvancedSearchParams,
    offset: number
): Promise<SearchResult> {
    const headers = {
        "content-type": "application/json",
    };

    const json_body = {
        ...(search.query && { global_search: search.query }),
        ...(search.type && { type: search.type }),
        ...(search.title && { title: search.title }),
        offset: offset,
        limit: SEARCH_LIMIT,
    };
    const body = JSON.stringify(json_body);

    const response = await post(`/api/v1/docman_search/${folder_id}`, { headers, body });

    const pagination_size = response.headers.get("X-PAGINATION-SIZE");
    if (pagination_size === null) {
        throw new Error("No X-PAGINATION-SIZE field in the header.");
    }

    const total = Number(pagination_size);
    const to = Math.min(total, offset + SEARCH_LIMIT) - 1;
    return {
        from: offset,
        to,
        total,
        items: await response.json(),
    };
}

function addNewFile(item: ItemFile, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/files",
        item
    );
}

function addNewEmpty(item: Empty, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/empties",
        item
    );
}

function addNewEmbedded(item: Embedded, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/embedded_files",
        item
    );
}

function addNewWiki(item: Wiki, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/wikis",
        item
    );
}

function addNewLink(item: Link, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/links",
        item
    );
}

function addNewFolder(item: Folder, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/folders",
        item
    );
}

async function createNewVersion(
    item: ItemFile,
    version_title: string,
    change_log: string,
    dropped_file: ItemFile,
    should_lock_file: boolean,
    approval_table_action: ApprovalTable | null
): Promise<CreatedItemFileProperties> {
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

function getFolderContent(folder_id: number): Promise<Array<Item>> {
    return recursiveGet("/api/docman_items/" + encodeURIComponent(folder_id) + "/docman_items", {
        params: {
            limit: 50,
            offset: 0,
        },
    });
}

function getParents(folder_id: number): Promise<Array<Item>> {
    return recursiveGet("/api/docman_items/" + encodeURIComponent(folder_id) + "/parents", {
        params: {
            limit: 50,
            offset: 0,
        },
    });
}

function postEmbeddedFile(
    item: Embedded,
    content: string,
    version_title: string,
    change_log: string,
    should_lock_file: boolean,
    approval_table_action: ApprovalTable | null
): Promise<Response> {
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

function postWiki(
    item: Wiki,
    page_name: string,
    version_title: string,
    change_log: string,
    should_lock_file: boolean
): Promise<Response> {
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
    item: Link,
    link_url: string,
    version_title: string,
    change_log: string,
    should_lock_file: boolean,
    approval_table_action: ApprovalTable | null
): Promise<Response> {
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

function cancelUpload(item: ItemFileUploader): Promise<Response> {
    return del(item.uploader.url, {
        headers: {
            "Tus-Resumable": "1.0.0",
        },
    });
}

function deleteFile(item: ItemFileUploader): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_files/${escaped_item_id}`);
}

function deleteLink(item: Link): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_links/${escaped_item_id}`);
}

function deleteEmbeddedFile(item: Embedded): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_embedded_files/${escaped_item_id}`);
}

function deleteWiki(
    item: Wiki,
    { delete_associated_wiki_page = false }: DeleteWikiPageOptions
): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    const escaped_option = encodeURIComponent(delete_associated_wiki_page);

    return del(
        `/api/docman_wikis/${escaped_item_id}?delete_associated_wiki_page=${escaped_option}`
    );
}

function deleteFolder(item: Folder): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_folders/${escaped_item_id}`);
}

function deleteEmptyDocument(item: Empty): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_empty_documents/${escaped_item_id}`);
}

async function getItemsReferencingSameWikiPage(page_id: number): Promise<Item> {
    const escaped_page_id = encodeURIComponent(page_id);
    const response = await get(`/api/phpwiki/${escaped_page_id}/items_referencing_wiki_page`);

    return response.json();
}

async function getProjectUserGroups(project_id: number): Promise<Array<UserGroup>> {
    const response = await get(
        "/api/projects/" +
            encodeURIComponent(project_id) +
            "/user_groups?query=" +
            encodeURIComponent(JSON.stringify({ with_system_user_groups: true }))
    );

    return response.json();
}

function postNewLinkVersionFromEmpty(item_id: number, link_url: string): Promise<Response> {
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

function postNewEmbeddedFileVersionFromEmpty(item_id: number, content: string): Promise<Response> {
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

async function postNewFileVersionFromEmpty(
    item_id: number,
    dropped_file: ItemFile
): Promise<CreatedItemFileProperties> {
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

async function getItemWithSize(folder_id: number): Promise<Folder> {
    const response = await get(`/api/docman_items/${encodeURIComponent(folder_id)}?with_size=true`);

    return response.json();
}
