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

import { describe, expect, it, vi } from "vitest";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import {
    putEmbeddedFilePermissions,
    putEmptyDocumentPermissions,
    putFilePermissions,
    putFolderPermissions,
    putLinkPermissions,
    putOtherTypeDocumentPermissions,
    putWikiPermissions,
} from "./permissions-rest-querier";
import type { Permissions } from "../type";

describe("Update item permissions", () => {
    const item_id = 123;
    const permissions = {
        apply_permissions_on_children: true,
        can_read: [],
        can_write: [],
        can_manage: [],
    } as Permissions;

    it("Update permissions of a file", async () => {
        const tlpPut = vi.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);

        await putFilePermissions(item_id, permissions);

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_files/${item_id}/permissions`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify(permissions),
        });
    });

    it("Update permissions of an embedded file", async () => {
        const tlpPut = vi.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);

        await putEmbeddedFilePermissions(item_id, permissions);

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_embedded_files/${item_id}/permissions`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify(permissions),
        });
    });

    it("Update permissions of a link", async () => {
        const tlpPut = vi.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);
        await putLinkPermissions(item_id, permissions);

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_links/${item_id}/permissions`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify(permissions),
        });
    });

    it("Update permissions of a wiki document", async () => {
        const tlpPut = vi.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);
        await putWikiPermissions(item_id, permissions);

        expect(tlp_fetch.put).toHaveBeenCalledWith(`/api/docman_wikis/${item_id}/permissions`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify(permissions),
        });
    });

    it("Update permissions of an empty document", async () => {
        const tlpPut = vi.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);
        await putEmptyDocumentPermissions(item_id, permissions);

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_empty_documents/${item_id}/permissions`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify(permissions),
        });
    });

    it("Update permissions of another type document", async () => {
        const tlpPut = vi.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);
        await putOtherTypeDocumentPermissions(item_id, permissions);

        expect(tlpPut).toHaveBeenCalledWith(
            `/api/docman_other_type_documents/${item_id}/permissions`,
            {
                headers: expect.objectContaining({ "Content-Type": "application/json" }),
                body: JSON.stringify(permissions),
            },
        );
    });

    it("Update permissions of folder", async () => {
        const tlpPut = vi.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);
        await putFolderPermissions(item_id, permissions);

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_folders/${item_id}/permissions`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify(permissions),
        });
    });
});
