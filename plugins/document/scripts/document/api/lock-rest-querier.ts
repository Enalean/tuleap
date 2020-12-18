/*
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
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
import { Item } from "../type";

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

function postLockFile(item: Item): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_files/${escaped_item_id}/lock`, { headers });
}

function deleteLockFile(item: Item): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_files/${escaped_item_id}/lock`);
}

function postLockEmbedded(item: Item): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_embedded_files/${escaped_item_id}/lock`, { headers });
}

function deleteLockEmbedded(item: Item): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_embedded_files/${escaped_item_id}/lock`);
}

function postLockWiki(item: Item): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_wikis/${escaped_item_id}/lock`, { headers });
}

function deleteLockWiki(item: Item): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_wikis/${escaped_item_id}/lock`);
}

function postLockLink(item: Item): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_links/${escaped_item_id}/lock`, { headers });
}

function deleteLockLink(item: Item): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_links/${escaped_item_id}/lock`);
}

function postLockEmpty(item: Item): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const escaped_item_id = encodeURIComponent(item.id);

    return post(`/api/docman_empty_documents/${escaped_item_id}/lock`, { headers });
}

function deleteLockEmpty(item: Item): Promise<Response> {
    const escaped_item_id = encodeURIComponent(item.id);

    return del(`/api/docman_empty_documents/${escaped_item_id}/lock`);
}
