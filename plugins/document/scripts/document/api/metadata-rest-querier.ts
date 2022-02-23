/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import { put, recursiveGet } from "tlp";
import type { FolderStatus, Property } from "../store/metadata/module";

export {
    putFileMetadata,
    putEmbeddedFileMetadata,
    putLinkMetadata,
    putWikiMetadata,
    putEmptyDocumentMetadata,
    putFolderDocumentMetadata,
    getProjectMetadata,
};

function putFileMetadata(
    id: number,
    title: string,
    description: string,
    owner_id: number,
    status: string | null,
    obsolescence_date: number | null,
    metadata: Array<Property> | null
): Promise<Response> {
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
    id: number,
    title: string,
    description: string,
    owner_id: number,
    status: string | null,
    obsolescence_date: number | null,
    metadata: Array<Property> | null
): Promise<Response> {
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

function putLinkMetadata(
    id: number,
    title: string,
    description: string,
    owner_id: number,
    status: string | null,
    obsolescence_date: number | null,
    metadata: Array<Property> | null
): Promise<Response> {
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

function putWikiMetadata(
    id: number,
    title: string,
    description: string,
    owner_id: number,
    status: string | null,
    obsolescence_date: number | null,
    metadata: Array<Property> | null
): Promise<Response> {
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
    id: number,
    title: string,
    description: string,
    owner_id: number,
    status: string | null,
    obsolescence_date: number | null,
    metadata: Array<Property> | null
): Promise<Response> {
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
    id: number,
    title: string,
    description: string,
    owner_id: number,
    status: FolderStatus | null,
    obsolescence_date: number | null,
    metadata: Array<Property> | null
): Promise<Response> {
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

function getProjectMetadata(project_id: number): Promise<Array<Property>> {
    const escaped_project_id = encodeURIComponent(project_id);
    return recursiveGet(`/api/projects/${escaped_project_id}/docman_metadata`, {
        params: {
            limit: 50,
            offset: 0,
        },
    });
}
