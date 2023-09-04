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

import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import {
    getProjectProperties,
    putEmbeddedFileProperties,
    putEmptyDocumentProperties,
    putFileProperties,
    putFolderDocumentProperties,
    putLinkProperties,
} from "./properties-rest-querier";

describe("properties rest querier", () => {
    const id = 1234;
    const title = "My document";
    const description = "description";
    const owner_id = 101;
    const status = null;
    const obsolescence_date = null;
    const properties = null;

    it("Update properties of a file", async () => {
        const tlpPut = jest.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);

        await putFileProperties(
            id,
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            properties,
        );

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_files/${id}/metadata`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify({
                title,
                description,
                owner_id,
                status,
                obsolescence_date,
                metadata: properties,
            }),
        });
    });

    it("Update properties of an embbeded file", async () => {
        const tlpPut = jest.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);

        await putEmbeddedFileProperties(
            id,
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            properties,
        );

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_embedded_files/${id}/metadata`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify({
                title,
                description,
                owner_id,
                status,
                obsolescence_date,
                metadata: properties,
            }),
        });
    });

    it("Update properties of an link", async () => {
        const tlpPut = jest.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);

        await putLinkProperties(
            id,
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            properties,
        );

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_links/${id}/metadata`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify({
                title,
                description,
                owner_id,
                status,
                obsolescence_date,
                metadata: properties,
            }),
        });
    });

    it("Update properties of empty", async () => {
        const tlpPut = jest.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);

        await putEmptyDocumentProperties(
            id,
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            properties,
        );

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_empty_documents/${id}/metadata`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify({
                title,
                description,
                owner_id,
                status,
                obsolescence_date,
                metadata: properties,
            }),
        });
    });

    it("Update properties of folder", async () => {
        const tlpPut = jest.spyOn(tlp_fetch, "put");
        mockFetchSuccess(tlpPut);

        await putFolderDocumentProperties(
            id,
            title,
            description,
            owner_id,
            status,
            obsolescence_date,
            properties,
        );

        expect(tlpPut).toHaveBeenCalledWith(`/api/docman_folders/${id}/metadata`, {
            headers: expect.objectContaining({ "Content-Type": "application/json" }),
            body: JSON.stringify({
                title,
                description,
                owner_id,
                status,
                obsolescence_date,
                metadata: properties,
            }),
        });
    });

    it("get project properties", async () => {
        const tlpRecursiveGet = jest.spyOn(tlp_fetch, "recursiveGet");
        mockFetchSuccess(tlpRecursiveGet);

        const project_id = 101;
        await getProjectProperties(project_id);

        expect(tlpRecursiveGet).toHaveBeenCalledWith(
            `/api/projects/${project_id}/docman_metadata`,
            {
                params: {
                    limit: 50,
                    offset: 0,
                },
            },
        );
    });
});
