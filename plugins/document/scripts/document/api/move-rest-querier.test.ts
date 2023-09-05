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

import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import {
    copyEmbedded,
    copyEmpty,
    copyFile,
    copyFolder,
    copyLink,
    copyWiki,
    moveEmbedded,
    moveEmpty,
    moveFile,
    moveFolder,
    moveLink,
    moveWiki,
} from "./move-rest-querier";

describe("Move item", () => {
    const moved_item_id = 147;
    const destination_folder_id = 852;

    it("Move a file", async () => {
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch);
        await moveFile(moved_item_id, destination_folder_id);

        expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_files/${moved_item_id}`, {
            headers: expect.objectContaining({ "content-type": "application/json" }),
            body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
        });
    });

    it("Move an empty document", async () => {
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch);
        await moveEmpty(moved_item_id, destination_folder_id);

        expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_empty_documents/${moved_item_id}`, {
            headers: expect.objectContaining({ "content-type": "application/json" }),
            body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
        });
    });

    it("Move an embedded document", async () => {
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch);
        await moveEmbedded(moved_item_id, destination_folder_id);

        expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_embedded_files/${moved_item_id}`, {
            headers: expect.objectContaining({ "content-type": "application/json" }),
            body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
        });
    });

    it("Move a wiki document", async () => {
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch);
        await moveWiki(moved_item_id, destination_folder_id);

        expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_wikis/${moved_item_id}`, {
            headers: expect.objectContaining({ "content-type": "application/json" }),
            body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
        });
    });

    it("Move a link document", async () => {
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch);
        await moveLink(moved_item_id, destination_folder_id);

        expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_links/${moved_item_id}`, {
            headers: expect.objectContaining({ "content-type": "application/json" }),
            body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
        });
    });

    it("Move a folder", async () => {
        const tlpPatch = jest.spyOn(tlp_fetch, "patch");
        mockFetchSuccess(tlpPatch);
        await moveFolder(moved_item_id, destination_folder_id);

        expect(tlpPatch).toHaveBeenCalledWith(`/api/docman_folders/${moved_item_id}`, {
            headers: expect.objectContaining({ "content-type": "application/json" }),
            body: JSON.stringify({ move: { destination_folder_id: destination_folder_id } }),
        });
    });
});

describe("Copy item", () => {
    const copied_item_id = 147;
    const destination_folder_id = 852;

    it("Create a copy of a file", async () => {
        const tlpPost = jest.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost, { return_json: JSON.stringify({ id: 963, uri: "path/to/963" }) });
        await copyFile(copied_item_id, destination_folder_id);

        expect(tlpPost).toHaveBeenCalledWith(`/api/docman_folders/${destination_folder_id}/files`, {
            headers: expect.objectContaining({ "content-type": "application/json" }),
            body: JSON.stringify({ copy: { item_id: copied_item_id } }),
        });
    });

    it("Create a copy of an empty document", async () => {
        const tlpPost = jest.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost, { return_json: JSON.stringify({ id: 963, uri: "path/to/963" }) });
        await copyEmpty(copied_item_id, destination_folder_id);

        expect(tlpPost).toHaveBeenCalledWith(
            `/api/docman_folders/${destination_folder_id}/empties`,
            {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: JSON.stringify({ copy: { item_id: copied_item_id } }),
            },
        );
    });

    it("Create a copy of an embedded document", async () => {
        const tlpPost = jest.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost, { return_json: JSON.stringify({ id: 963, uri: "path/to/963" }) });
        await copyEmbedded(copied_item_id, destination_folder_id);

        expect(tlpPost).toHaveBeenCalledWith(
            `/api/docman_folders/${destination_folder_id}/embedded_files`,
            {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: JSON.stringify({ copy: { item_id: copied_item_id } }),
            },
        );
    });

    it("Create a copy of a wiki document", async () => {
        const tlpPost = jest.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost, { return_json: JSON.stringify({ id: 963, uri: "path/to/963" }) });
        await copyWiki(copied_item_id, destination_folder_id);

        expect(tlpPost).toHaveBeenCalledWith(`/api/docman_folders/${destination_folder_id}/wikis`, {
            headers: expect.objectContaining({ "content-type": "application/json" }),
            body: JSON.stringify({ copy: { item_id: copied_item_id } }),
        });
    });

    it("Create a copy of a link document", async () => {
        const tlpPost = jest.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost, { return_json: JSON.stringify({ id: 963, uri: "path/to/963" }) });
        await copyLink(copied_item_id, destination_folder_id);

        expect(tlpPost).toHaveBeenCalledWith(`/api/docman_folders/${destination_folder_id}/links`, {
            headers: expect.objectContaining({ "content-type": "application/json" }),
            body: JSON.stringify({ copy: { item_id: copied_item_id } }),
        });
    });

    it("Create a copy of a folder", async () => {
        const tlpPost = jest.spyOn(tlp_fetch, "post");
        mockFetchSuccess(tlpPost, { return_json: JSON.stringify({ id: 963, uri: "path/to/963" }) });
        await copyFolder(copied_item_id, destination_folder_id);

        expect(tlpPost).toHaveBeenCalledWith(
            `/api/docman_folders/${destination_folder_id}/folders`,
            {
                headers: expect.objectContaining({ "content-type": "application/json" }),
                body: JSON.stringify({ copy: { item_id: copied_item_id } }),
            },
        );
    });
});
