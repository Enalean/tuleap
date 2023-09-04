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

import { put } from "@tuleap/tlp-fetch";
import type { Permissions } from "../type";

export async function putEmbeddedFilePermissions(
    id: number,
    permissions: Permissions,
): Promise<void> {
    await put(`/api/docman_embedded_files/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

export async function putFilePermissions(id: number, permissions: Permissions): Promise<void> {
    await put(`/api/docman_files/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

export async function putLinkPermissions(id: number, permissions: Permissions): Promise<void> {
    await put(`/api/docman_links/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

export async function putWikiPermissions(id: number, permissions: Permissions): Promise<void> {
    await put(`/api/docman_wikis/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

export async function putEmptyDocumentPermissions(
    id: number,
    permissions: Permissions,
): Promise<void> {
    await put(`/api/docman_empty_documents/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}

export async function putFolderPermissions(id: number, permissions: Permissions): Promise<void> {
    await put(`/api/docman_folders/${encodeURIComponent(id)}/permissions`, {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify(permissions),
    });
}
