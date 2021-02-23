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

import { del, post } from "tlp";
import type { Embedded, Empty, ItemFile, Link, Wiki } from "../type";

export {
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
};

function postLockFile(item: ItemFile): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_files/${escaped_item_id}/lock`, { headers });
}

function deleteLockFile(item: ItemFile): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_files/${escaped_item_id}/lock`);
}

function postLockEmbedded(item: Embedded): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_embedded_files/${escaped_item_id}/lock`, { headers });
}

function deleteLockEmbedded(item: Embedded): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_embedded_files/${escaped_item_id}/lock`);
}

function postLockWiki(item: Wiki): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_wikis/${escaped_item_id}/lock`, { headers });
}

function deleteLockWiki(item: Wiki): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_wikis/${escaped_item_id}/lock`);
}

function postLockLink(item: Link): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_links/${escaped_item_id}/lock`, { headers });
}

function deleteLockLink(item: Link): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_links/${escaped_item_id}/lock`);
}

function postLockEmpty(item: Empty): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_empty_documents/${escaped_item_id}/lock`, { headers });
}

function deleteLockEmpty(item: Empty): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_empty_documents/${escaped_item_id}/lock`);
}
