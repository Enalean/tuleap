/*
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import { del, post } from "@tuleap/tlp-fetch";
import type { Embedded, Empty, ItemFile, Link, Wiki } from "../type";

export async function postLockFile(item: ItemFile): Promise<void> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    await post(`/api/docman_files/${escaped_item_id}/lock`, { headers });
}

export async function deleteLockFile(item: ItemFile): Promise<void> {
    const escaped_item_id = encodeURIComponent(item.id);

    await del(`/api/docman_files/${escaped_item_id}/lock`);
}

export async function postLockEmbedded(item: Embedded): Promise<void> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    await post(`/api/docman_embedded_files/${escaped_item_id}/lock`, { headers });
}

export async function deleteLockEmbedded(item: Embedded): Promise<void> {
    const escaped_item_id = encodeURIComponent(item.id);

    await del(`/api/docman_embedded_files/${escaped_item_id}/lock`);
}

export async function postLockWiki(item: Wiki): Promise<void> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    await post(`/api/docman_wikis/${escaped_item_id}/lock`, { headers });
}

export async function deleteLockWiki(item: Wiki): Promise<void> {
    const escaped_item_id = encodeURIComponent(item.id);

    await del(`/api/docman_wikis/${escaped_item_id}/lock`);
}

export async function postLockLink(item: Link): Promise<void> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    await post(`/api/docman_links/${escaped_item_id}/lock`, { headers });
}

export async function deleteLockLink(item: Link): Promise<void> {
    const escaped_item_id = encodeURIComponent(item.id);

    await del(`/api/docman_links/${escaped_item_id}/lock`);
}

export async function postLockEmpty(item: Empty): Promise<void> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    await post(`/api/docman_empty_documents/${escaped_item_id}/lock`, { headers });
}

export async function deleteLockEmpty(item: Empty): Promise<void> {
    const escaped_item_id = encodeURIComponent(item.id);

    await del(`/api/docman_empty_documents/${escaped_item_id}/lock`);
}
