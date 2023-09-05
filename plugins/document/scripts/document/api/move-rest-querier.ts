/**
 *  Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { patch, post } from "@tuleap/tlp-fetch";
import type { Item } from "../type";

export function moveFile(moved_item_id: number, parent_id: number): Promise<void> {
    return moveDocumentType("/api/docman_files/" + encodeURIComponent(moved_item_id), parent_id);
}

export function moveEmpty(moved_item_id: number, parent_id: number): Promise<void> {
    return moveDocumentType(
        "/api/docman_empty_documents/" + encodeURIComponent(moved_item_id),
        parent_id,
    );
}

export function moveEmbedded(moved_item_id: number, parent_id: number): Promise<void> {
    return moveDocumentType(
        "/api/docman_embedded_files/" + encodeURIComponent(moved_item_id),
        parent_id,
    );
}

export function moveWiki(moved_item_id: number, parent_id: number): Promise<void> {
    return moveDocumentType("/api/docman_wikis/" + encodeURIComponent(moved_item_id), parent_id);
}

export function moveLink(moved_item_id: number, parent_id: number): Promise<void> {
    return moveDocumentType("/api/docman_links/" + encodeURIComponent(moved_item_id), parent_id);
}

export function moveFolder(moved_item_id: number, parent_id: number): Promise<void> {
    return moveDocumentType("/api/docman_folders/" + encodeURIComponent(moved_item_id), parent_id);
}

export async function moveDocumentType(url: string, destination_folder_id: number): Promise<void> {
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

export async function copyDocumentType(url: string, copied_item_id: number): Promise<Item> {
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

export function copyFile(copied_item_id: number, parent_id: number): Promise<Item> {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/files",
        copied_item_id,
    );
}

export function copyEmpty(copied_item_id: number, parent_id: number): Promise<Item> {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/empties",
        copied_item_id,
    );
}

export function copyEmbedded(copied_item_id: number, parent_id: number): Promise<Item> {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/embedded_files",
        copied_item_id,
    );
}

export function copyWiki(copied_item_id: number, parent_id: number): Promise<Item> {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/wikis",
        copied_item_id,
    );
}

export function copyLink(copied_item_id: number, parent_id: number): Promise<Item> {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/links",
        copied_item_id,
    );
}

export function copyFolder(copied_item_id: number, parent_id: number): Promise<Item> {
    return copyDocumentType(
        "/api/docman_folders/" + encodeURIComponent(parent_id) + "/folders",
        copied_item_id,
    );
}
