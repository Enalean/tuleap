/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { createPinia, defineStore, setActivePinia } from "pinia";

const search = jest.fn();
const focusFirstSearchResult = jest.fn();
jest.mock("./fulltext", () => {
    return {
        useFullTextStore: defineStore("fulltext", {
            actions: {
                search,
                focusFirstSearchResult,
            },
        }),
    };
});

import * as tlp from "@tuleap/tlp-fetch";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { Project, ItemDefinition } from "../type";
import { useSwitchToStore } from "./index";

describe("Root store", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe("Actions", () => {
        describe("loadHistory", () => {
            it("Rethrow API error", async () => {
                const tlpGetMock = jest.spyOn(tlp, "get");

                mockFetchError(tlpGetMock, {});

                const store = useSwitchToStore();
                store.$patch({
                    user_id: 102,
                    is_history_loaded: false,
                });

                await expect(store.loadHistory()).rejects.toBeDefined();

                expect(store.is_history_in_error).toBe(true);
            });

            it("Fetch user history", async () => {
                const tlpGetMock = jest.spyOn(tlp, "get");

                mockFetchSuccess(tlpGetMock, {
                    return_json: {
                        entries: [{ xref: "art #1" }],
                    },
                });

                const store = useSwitchToStore();
                store.$patch({
                    user_id: 102,
                    is_history_loaded: false,
                });

                await store.loadHistory();

                expect(store.history).toStrictEqual({
                    entries: [{ xref: "art #1" }],
                });
            });

            it("Does not fetch user history if it has already been loaded", async () => {
                const tlpGetMock = jest.spyOn(tlp, "get");

                const store = useSwitchToStore();
                store.$patch({
                    user_id: 102,
                    is_history_loaded: true,
                });

                await store.loadHistory();

                expect(tlpGetMock).not.toHaveBeenCalled();
            });
        });

        describe("updateFilterValue", () => {
            it("updates the filter value in the store and ask to perform a fulltext search", () => {
                const store = useSwitchToStore();
                store.$patch({
                    filter_value: "acme",
                });

                expect(store.filter_value).toBe("acme");

                store.updateFilterValue("abc");

                expect(store.filter_value).toBe("abc");
                expect(search).toHaveBeenCalled();
            });

            it("does not ask to perform a fulltext search if it is the same value as before", () => {
                const store = useSwitchToStore();
                store.$patch({
                    filter_value: "acme",
                });

                expect(store.filter_value).toBe("acme");

                store.updateFilterValue("acme");

                expect(search).not.toHaveBeenCalled();
            });
        });
    });

    describe("Getters", () => {
        describe("filtered_projects", () => {
            it("Filters projects", () => {
                const store = useSwitchToStore();
                store.$patch({
                    projects: [
                        { project_name: "Acme" } as Project,
                        { project_name: "ACME Corp" } as Project,
                        { project_name: "Another project" } as Project,
                    ],
                    filter_value: "acme",
                });

                expect(store.filtered_projects).toStrictEqual([
                    { project_name: "Acme" } as Project,
                    { project_name: "ACME Corp" } as Project,
                ]);
            });
        });

        describe("filtered_history", () => {
            it("Filters recent items", () => {
                const store = useSwitchToStore();
                store.$patch({
                    history: {
                        entries: [
                            { title: "Acme" } as ItemDefinition,
                            { title: "ACME Corp" } as ItemDefinition,
                            { title: "Another entry" } as ItemDefinition,
                            { xref: "wiki #ACME" } as ItemDefinition,
                        ],
                    },
                    filter_value: "acme",
                });

                expect(store.filtered_history).toStrictEqual({
                    entries: [
                        { title: "Acme" } as ItemDefinition,
                        { title: "ACME Corp" } as ItemDefinition,
                        { xref: "wiki #ACME" } as ItemDefinition,
                    ],
                });
            });
        });
    });
});
