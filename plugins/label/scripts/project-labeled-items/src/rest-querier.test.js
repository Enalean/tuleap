/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import { describe, afterEach, vi, it, expect } from "vitest";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { getLabeledItems } from "./rest-querier.js";

import * as tlp_fetch from "@tuleap/tlp-fetch";

vi.mock("@tuleap/tlp-fetch");

describe("getLabeledItems", () => {
    const project_id = 101;
    const labels_id = [3, 4];

    afterEach(() => {
        vi.clearAllMocks();
    });

    it("Returns the items", async () => {
        const headers = {
            /** 'X-PAGINATION-SIZE' */
            get: () => 10,
        };
        const return_json = {
            labeled_items: [{ title: "Le title" }],
            are_there_items_user_cannot_see: false,
        };
        mockFetchSuccess(vi.spyOn(tlp_fetch, "get"), { headers, return_json });

        const { labeled_items } = await getLabeledItems(project_id, labels_id, 0, 1);

        expect(labeled_items).toEqual([{ title: "Le title" }]);
    });

    it("Returns the are_there_items_user_cannot_see flag", async () => {
        const headers = {
            /** 'X-PAGINATION-SIZE' */
            get: () => 10,
        };
        const return_json = {
            labeled_items: [{ title: "Le title" }],
            are_there_items_user_cannot_see: false,
        };
        mockFetchSuccess(vi.spyOn(tlp_fetch, "get"), { headers, return_json });

        const { are_there_items_user_cannot_see } = await getLabeledItems(
            project_id,
            labels_id,
            0,
            1,
        );

        expect(are_there_items_user_cannot_see).toBe(false);
    });

    it("Sets has_more to true if there are still elements to fetch", async () => {
        const headers = {
            /** 'X-PAGINATION-SIZE' */
            get: () => 10,
        };
        const return_json = {
            labeled_items: [{ title: "Le title" }],
            are_there_items_user_cannot_see: false,
        };
        mockFetchSuccess(vi.spyOn(tlp_fetch, "get"), { headers, return_json });

        const { has_more } = await getLabeledItems(project_id, labels_id, 0, 1);

        expect(has_more).toBe(true);
    });

    it("Sets has_more to false if there are no more elements to fetch", async () => {
        const headers = {
            /** 'X-PAGINATION-SIZE' */
            get: () => 10,
        };
        const return_json = {
            labeled_items: [{ title: "Le title" }],
            are_there_items_user_cannot_see: false,
        };
        mockFetchSuccess(vi.spyOn(tlp_fetch, "get"), { headers, return_json });

        const { has_more } = await getLabeledItems(project_id, labels_id, 9, 1);

        expect(has_more).toBe(false);
    });

    it("Returns the offset so that the caller update its offset in case of recursive calls", async () => {
        const headers = {
            /** 'X-PAGINATION-SIZE' */
            get: () => 10,
        };
        const return_json = {
            labeled_items: [{ title: "Le title" }],
            are_there_items_user_cannot_see: false,
        };
        mockFetchSuccess(vi.spyOn(tlp_fetch, "get"), { headers, return_json });

        const { offset } = await getLabeledItems(project_id, labels_id, 9, 1);

        expect(offset).toBe(9);
    });

    it("Fetches items recursively until it finds at least one readable", async () => {
        const tlpGet = vi.spyOn(tlp_fetch, "get");
        tlpGet
            .mockReturnValueOnce(
                Promise.resolve({
                    headers: {
                        /** 'X-PAGINATION-SIZE' */
                        get: () => 10,
                    },
                    json: () =>
                        Promise.resolve({
                            labeled_items: [],
                            are_there_items_user_cannot_see: true,
                        }),
                }),
            )
            .mockReturnValueOnce(
                Promise.resolve({
                    headers: {
                        /** 'X-PAGINATION-SIZE' */
                        get: () => 10,
                    },
                    json: () =>
                        Promise.resolve({
                            labeled_items: [],
                            are_there_items_user_cannot_see: true,
                        }),
                }),
            )
            .mockReturnValueOnce(
                Promise.resolve({
                    headers: {
                        /** 'X-PAGINATION-SIZE' */
                        get: () => 10,
                    },
                    json: () =>
                        Promise.resolve({
                            labeled_items: [{ title: "Le title" }],
                            are_there_items_user_cannot_see: false,
                        }),
                }),
            );

        const { offset, labeled_items } = await getLabeledItems(project_id, labels_id, 0, 1);

        expect(tlpGet.mock.calls).toHaveLength(3);
        expect(tlpGet.mock.calls[0]).toEqual([
            "/api/projects/" + project_id + "/labeled_items",
            {
                params: {
                    query: JSON.stringify({ labels_id }),
                    offset: 0,
                    limit: 1,
                },
            },
        ]);
        expect(tlpGet.mock.calls[1]).toEqual([
            "/api/projects/" + project_id + "/labeled_items",
            {
                params: {
                    query: JSON.stringify({ labels_id }),
                    offset: 1,
                    limit: 1,
                },
            },
        ]);
        expect(tlpGet.mock.calls[2]).toEqual([
            "/api/projects/" + project_id + "/labeled_items",
            {
                params: {
                    query: JSON.stringify({ labels_id }),
                    offset: 2,
                    limit: 1,
                },
            },
        ]);
        expect(offset).toBe(2);
        expect(labeled_items).toEqual([{ title: "Le title" }]);
    });
});
