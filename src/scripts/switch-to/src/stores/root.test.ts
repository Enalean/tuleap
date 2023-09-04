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
import { useRootStore } from "./root";

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

                const store = useRootStore();
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

                const store = useRootStore();
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

                const store = useRootStore();
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
                const store = useRootStore();
                store.$patch({
                    filter_value: "acme",
                });

                expect(store.filter_value).toBe("acme");

                store.updateFilterValue("abc");

                expect(store.filter_value).toBe("abc");
                expect(search).toHaveBeenCalled();
            });

            it("does not ask to perform a fulltext search if it is the same value as before", () => {
                const store = useRootStore();
                store.$patch({
                    filter_value: "acme",
                });

                expect(store.filter_value).toBe("acme");

                store.updateFilterValue("acme");

                expect(search).not.toHaveBeenCalled();
            });

            it("does not ask to perform a fulltext search if there is no keywords", () => {
                const store = useRootStore();
                store.$patch({
                    filter_value: "",
                });

                store.updateFilterValue("   ");

                expect(search).not.toHaveBeenCalled();
            });

            it("should pass the keywords and not the raw filter_value", () => {
                const store = useRootStore();
                store.$patch({
                    filter_value: "",
                });

                store.updateFilterValue("  foo  ");

                expect(search).toHaveBeenCalledWith("foo");
            });
        });
    });

    describe("Getters", () => {
        describe("filtered_projects", () => {
            it("Filters projects", () => {
                const store = useRootStore();
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

            it("No filtered projects when filter is only spaces", () => {
                const store = useRootStore();
                store.$patch({
                    projects: [
                        { project_name: "Acme" } as Project,
                        { project_name: "ACME Corp" } as Project,
                        { project_name: "Another project" } as Project,
                    ],
                    filter_value: "   ",
                });

                expect(store.filtered_projects).toStrictEqual(store.projects);
            });
        });

        describe("filtered_history", () => {
            it("Filters recent items", () => {
                const store = useRootStore();
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

                expect(store.filtered_history.entries).toStrictEqual([
                    { title: "Acme" } as ItemDefinition,
                    { title: "ACME Corp" } as ItemDefinition,
                    { xref: "wiki #ACME" } as ItemDefinition,
                ]);
            });

            it("No filtered recent items when filter is only spaces", () => {
                const store = useRootStore();
                store.$patch({
                    history: {
                        entries: [
                            { title: "Acme" } as ItemDefinition,
                            { title: "ACME Corp" } as ItemDefinition,
                            { title: "Another entry" } as ItemDefinition,
                            { xref: "wiki #ACME" } as ItemDefinition,
                        ],
                    },
                    filter_value: "   ",
                });

                expect(store.filtered_history).toStrictEqual(store.history);
            });
        });

        describe("keywords", () => {
            it.each([
                ["   ", ""],
                ["  acme  ", "acme"],
            ])("should trim the filter_value '%s' to '%s'", (filter_value, expected_keywords) => {
                const store = useRootStore();
                store.$patch({
                    filter_value,
                });

                expect(store.keywords).toBe(expected_keywords);
            });
        });

        describe("is_in_search_mode", () => {
            it.each([
                ["", false],
                ["   ", false],
                ["  acme  ", true],
            ])(
                "when filter_value is '%s' then is_in_search_mode is %s",
                (filter_value, expected_is_in_search_mode) => {
                    const store = useRootStore();
                    store.$patch({
                        filter_value,
                    });

                    expect(store.is_in_search_mode).toBe(expected_is_in_search_mode);
                },
            );
        });
    });
});
