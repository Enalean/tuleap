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

import { del, get, post, recursiveGet } from "@tuleap/tlp-fetch";
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
    UserGroup,
    SearchResult,
    AdvancedSearchParams,
    CreatedItem,
    CreatedItemFileProperties,
    Property,
    Uploadable,
    ItemReferencingWikiPageRepresentation,
} from "../type";
import { SEARCH_LIMIT } from "../type";
import { getRestBodyFromSearchParams } from "../helpers/get-rest-body-from-search-params";
import {
    convertArrayOfItems,
    convertRestItemToItem,
} from "../helpers/properties-helpers/metadata-to-properties";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { getJSON, uri } from "@tuleap/fetch-result";

export interface RestItem extends Omit<Item, "properties"> {
    readonly metadata: Array<Property>;
}

export interface RestFolder extends Omit<Folder, "properties"> {
    readonly metadata: Array<Property>;
}

export interface PostRestItemFile {
    readonly title: string;
    readonly description?: string;
    readonly metadata?: Array<Property> | null;
    readonly file_properties?: {
        readonly file_name: string;
        readonly file_size: number;
    };
    readonly status?: string | null;
    readonly obsolescence_date?: string | null;
    readonly permissions_for_groups?: Permissions | null;
}

export interface RestLink extends Omit<Link, "properties"> {
    readonly metadata: Array<Property>;
}

export interface RestEmbedded extends Omit<Embedded, "properties"> {
    readonly metadata: Array<Property>;
}

export interface RestWiki extends Omit<Wiki, "properties"> {
    readonly metadata: Array<Property>;
}

export interface RestEmpty extends Omit<Empty, "properties"> {
    readonly metadata: Array<Property>;
}

export interface ProjectService {
    permissions_for_groups: AdminPermissions;
    root_item: RestFolder;
}

interface DeleteWikiPageOptions {
    delete_associated_wiki_page: boolean;
}

export interface RestUser {
    readonly id: string;
    readonly display_name: string;
    readonly username: string;
    readonly realname: string;
}

export function getUserByName(username: string): ResultAsync<RestUser[], Fault> {
    return getJSON<RestUser[]>(uri`/api/v1/users`, {
        params: {
            query: JSON.stringify({ username }),
            limit: 1,
            offset: 0,
        },
    });
}
export async function getDocumentManagerServiceInformation(
    project_id: number,
): Promise<ProjectService> {
    const response = await get(
        "/api/projects/" + encodeURIComponent(project_id) + "/docman_service",
    );

    return response.json();
}

export async function getItem(id: number): Promise<Item> {
    const response = await get("/api/docman_items/" + encodeURIComponent(id));

    const item: RestItem = await response.json();
    return convertRestItemToItem(item);
}

export async function addNewDocumentType(
    url: string,
    item: RestItem | PostRestItemFile,
): Promise<CreatedItem> {
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

export async function searchInFolder(
    folder_id: number,
    search: AdvancedSearchParams,
    offset: number,
): Promise<SearchResult> {
    const headers = {
        "content-type": "application/json",
    };

    const json_body = {
        ...getRestBodyFromSearchParams(search),
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

export function addNewFile(item: PostRestItemFile, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/files",
        item,
    );
}

export function addNewEmpty(item: RestEmpty, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/empties",
        item,
    );
}

export function addNewEmbedded(item: RestEmbedded, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/embedded_files",
        item,
    );
}

export function addNewWiki(item: RestWiki, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/wikis",
        item,
    );
}

export function addNewLink(item: RestLink, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/links",
        item,
    );
}

export function addNewFolder(item: RestFolder, parent_id: number): Promise<CreatedItem> {
    return addNewDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/folders",
        item,
    );
}

export async function createNewVersion(
    item: ItemFile,
    version_title: string,
    change_log: string,
    dropped_file: File,
    should_lock_file: boolean,
    approval_table_action: ApprovalTable | null,
): Promise<CreatedItemFileProperties> {
    const response = await post(`/api/docman_files/${encodeURIComponent(item.id)}/versions`, {
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

export async function getFolderContent(folder_id: number): Promise<ReadonlyArray<Item>> {
    const items: Array<RestItem> = await recursiveGet(
        "/api/docman_items/" + encodeURIComponent(folder_id) + "/docman_items",
        {
            params: {
                limit: 50,
                offset: 0,
            },
        },
    );

    return convertArrayOfItems(items);
}

export async function getParents(folder_id: number): Promise<Array<Item>> {
    const parents = await recursiveGet(
        "/api/docman_items/" + encodeURIComponent(folder_id) + "/parents",
        {
            params: {
                limit: 50,
                offset: 0,
            },
        },
    );

    const items: ReadonlyArray<RestItem> = JSON.parse(JSON.stringify(parents));
    return convertArrayOfItems(items);
}

export function postEmbeddedFile(
    item: Embedded,
    content: string,
    version_title: string,
    change_log: string,
    should_lock_file: boolean,
    approval_table_action: ApprovalTable | null,
): Promise<Response> {
    return post(`/api/docman_embedded_files/${encodeURIComponent(item.id)}/versions`, {
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

export function postWiki(
    item: Wiki,
    page_name: string,
    version_title: string,
    change_log: string,
    should_lock_file: boolean,
): Promise<Response> {
    return post(`/api/docman_wikis/${encodeURIComponent(item.id)}/versions`, {
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

export function postLinkVersion(
    item: Link,
    link_url: string,
    version_title: string,
    change_log: string,
    should_lock_file: boolean,
    approval_table_action: ApprovalTable | null,
): Promise<Response> {
    return post(`/api/docman_links/${encodeURIComponent(item.id)}/versions`, {
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

export function cancelUpload(item: Uploadable): Promise<Response | void> {
    if (!item.uploader || !item.uploader.url) {
        return Promise.resolve();
    }

    return del(item.uploader.url, {
        headers: {
            "Tus-Resumable": "1.0.0",
        },
    });
}

export function deleteFile(item: ItemFile): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_files/${escaped_item_id}`);
}

export function deleteLink(item: Link): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_links/${escaped_item_id}`);
}

export function deleteEmbeddedFile(item: Embedded): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_embedded_files/${escaped_item_id}`);
}

export function deleteWiki(
    item: Wiki,
    { delete_associated_wiki_page = false }: DeleteWikiPageOptions,
): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    const escaped_option = encodeURIComponent(delete_associated_wiki_page);

    return del(
        `/api/docman_wikis/${escaped_item_id}?delete_associated_wiki_page=${escaped_option}`,
    );
}

export function deleteFolder(item: Folder): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_folders/${escaped_item_id}`);
}

export function deleteEmptyDocument(item: Empty): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);
    return del(`/api/docman_empty_documents/${escaped_item_id}`);
}

export async function getItemsReferencingSameWikiPage(
    page_id: number,
): Promise<ReadonlyArray<ItemReferencingWikiPageRepresentation>> {
    const escaped_page_id = encodeURIComponent(page_id);
    const response = await get(`/api/phpwiki/${escaped_page_id}/items_referencing_wiki_page`);

    return response.json();
}

export async function getProjectUserGroups(project_id: number): Promise<ReadonlyArray<UserGroup>> {
    const response = await get(
        "/api/projects/" +
            encodeURIComponent(project_id) +
            "/user_groups?query=" +
            encodeURIComponent(JSON.stringify({ with_system_user_groups: true })),
    );

    return response.json();
}

export function postNewLinkVersionFromEmpty(item_id: number, link_url: string): Promise<Response> {
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

export function postNewEmbeddedFileVersionFromEmpty(
    item_id: number,
    content: string,
): Promise<Response> {
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

export async function postNewFileVersionFromEmpty(
    item_id: number,
    dropped_file: File,
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

export async function getItemWithSize(folder_id: number): Promise<Folder> {
    const response = await get(`/api/docman_items/${encodeURIComponent(folder_id)}?with_size=true`);

    return response.json();
}
