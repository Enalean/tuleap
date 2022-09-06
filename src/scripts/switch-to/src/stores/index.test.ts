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
jest.mock("./fulltext", () => {
    return {
        useFullTextStore: defineStore("fulltext", {
            actions: {
                search,
            },
        }),
    };
});

import * as tlp from "@tuleap/tlp-fetch";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { Project, ItemEntry } from "../type";
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

        describe("changeFocusFromProject", () => {
            it("does nothing if user hits left key", () => {
                const project = {} as Project;

                const store = useSwitchToStore();
                store.$patch({
                    is_history_loaded: true,
                    is_history_in_error: false,
                    history: {
                        entries: [{ html_url: "/first", title: "a" } as ItemEntry],
                    },
                    programmatically_focused_element: project,
                });

                store.changeFocusFromProject({
                    project,
                    key: "ArrowLeft",
                });

                expect(store.programmatically_focused_element).toStrictEqual(project);
            });

            describe("When user hits ArrowRight", () => {
                it.each([
                    [
                        "the history is not loaded yet",
                        {
                            is_history_loaded: false,
                        },
                    ],
                    [
                        "the history is in error",
                        {
                            is_history_loaded: false,
                            is_history_in_error: true,
                        },
                    ],
                    [
                        "the history is empty",
                        {
                            is_history_loaded: true,
                            is_history_in_error: false,
                            history: {
                                entries: [],
                            },
                        },
                    ],
                ])("does nothing if %s", (description, partial_state) => {
                    const project = {} as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        ...partial_state,
                        programmatically_focused_element: project,
                    });

                    store.changeFocusFromProject({
                        project,
                        key: "ArrowRight",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(project);
                });

                it("focus the first history entry", () => {
                    const first_entry = { html_url: "/first", title: "a" } as ItemEntry;
                    const another_entry = { html_url: "/another", title: "b" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        is_history_loaded: true,
                        is_history_in_error: false,
                        history: {
                            entries: [first_entry, another_entry],
                        },
                    });

                    store.changeFocusFromProject({
                        project: {} as Project,
                        key: "ArrowRight",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(first_entry);
                });
            });

            describe("When user hits ArrowUp", () => {
                it("does nothing if the project list is empty", () => {
                    const project = {
                        project_name: "Guinea Pig",
                    } as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        projects: [],
                        programmatically_focused_element: project,
                    });

                    store.changeFocusFromProject({
                        project,
                        key: "ArrowUp",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(project);
                });

                it("does nothing if the project list contains only one element", () => {
                    const project = {
                        project_name: "Guinea Pig",
                        project_uri: "/guinea-pig",
                    } as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        projects: [project],
                        programmatically_focused_element: project,
                    });

                    store.changeFocusFromProject({
                        project,
                        key: "ArrowUp",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(project);
                });

                it("goes up", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "First",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "Another",
                    } as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        projects: [first_project, another_project],
                        programmatically_focused_element: another_project,
                    });

                    store.changeFocusFromProject({
                        project: another_project,
                        key: "ArrowUp",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(first_project);
                });

                it("goes around if the current is the first in the list", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "First",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "Another",
                    } as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        projects: [first_project, another_project],
                        programmatically_focused_element: another_project,
                    });

                    store.changeFocusFromProject({
                        project: first_project,
                        key: "ArrowUp",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(another_project);
                });
            });

            describe("When user hits ArrowDown", () => {
                it("does nothing if the project list is empty", () => {
                    const project = {
                        project_name: "Guinea Pig",
                    } as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        projects: [],
                        programmatically_focused_element: project,
                    });

                    store.changeFocusFromProject({
                        project,
                        key: "ArrowDown",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(project);
                });

                it("does nothing if the project list contains only one element", () => {
                    const project = {
                        project_name: "Guinea Pig",
                        project_uri: "/guinea-pig",
                    } as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        projects: [project],
                        programmatically_focused_element: project,
                    });

                    store.changeFocusFromProject({
                        project,
                        key: "ArrowDown",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(project);
                });

                it("goes down", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "First",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "Another",
                    } as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        projects: [first_project, another_project],
                        programmatically_focused_element: another_project,
                    });

                    store.changeFocusFromProject({
                        project: first_project,
                        key: "ArrowDown",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(another_project);
                });

                it("goes around if the current is the last in the list", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "First",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "Another",
                    } as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        projects: [first_project, another_project],
                        programmatically_focused_element: another_project,
                    });

                    store.changeFocusFromProject({
                        project: another_project,
                        key: "ArrowDown",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(first_project);
                });
            });
        });

        describe("changeFocusFromHistory", () => {
            it("does nothing if user hits right key", () => {
                const entry = { html_url: "/first", title: "a" } as ItemEntry;

                const store = useSwitchToStore();
                store.$patch({
                    history: {
                        entries: [entry],
                    },
                    projects: [{ project_uri: "/a", project_name: "a" } as Project],
                    programmatically_focused_element: entry,
                });

                store.changeFocusFromHistory({
                    entry,
                    key: "ArrowRight",
                });

                expect(store.programmatically_focused_element).toStrictEqual(entry);
            });

            describe("When user hits ArrowLeft", () => {
                it("does nothing if the project list is empty", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        projects: [],
                        programmatically_focused_element: entry,
                    });

                    store.changeFocusFromHistory({
                        entry,
                        key: "ArrowLeft",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("focus the first project", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemEntry;

                    const first_project = { project_uri: "/a", project_name: "a" } as Project;
                    const another_project = { project_uri: "/b", project_name: "b" } as Project;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        projects: [first_project, another_project],
                        programmatically_focused_element: entry,
                    });

                    store.changeFocusFromHistory({
                        entry,
                        key: "ArrowLeft",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(first_project);
                });
            });

            describe("When user hits ArrowUp", () => {
                it("does nothing if the history is empty", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [],
                        },
                        programmatically_focused_element: entry,
                    });

                    store.changeFocusFromHistory({
                        entry,
                        key: "ArrowUp",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("does nothing if the history contains only one element", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        programmatically_focused_element: entry,
                    });

                    store.changeFocusFromHistory({
                        entry,
                        key: "ArrowUp",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("goes up", () => {
                    const first_entry = { html_url: "/first", title: "a" } as ItemEntry;
                    const another_entry = { html_url: "/another", title: "b" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                        programmatically_focused_element: another_entry,
                    });

                    store.changeFocusFromHistory({
                        entry: another_entry,
                        key: "ArrowUp",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(first_entry);
                });

                it("goes around if the current is the first in the list", () => {
                    const first_entry = { html_url: "/first", title: "a" } as ItemEntry;
                    const another_entry = { html_url: "/another", title: "b" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                        programmatically_focused_element: first_entry,
                    });

                    store.changeFocusFromHistory({
                        entry: first_entry,
                        key: "ArrowUp",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(another_entry);
                });
            });

            describe("When user hits ArrowDown", () => {
                it("does nothing if the history is empty", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [],
                        },
                        programmatically_focused_element: entry,
                    });

                    store.changeFocusFromHistory({
                        entry,
                        key: "ArrowDown",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("does nothing if the history contains only one element", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        programmatically_focused_element: entry,
                    });

                    store.changeFocusFromHistory({
                        entry,
                        key: "ArrowDown",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("goes down", () => {
                    const first_entry = { html_url: "/first", title: "a" } as ItemEntry;
                    const another_entry = { html_url: "/another", title: "b" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                        programmatically_focused_element: first_entry,
                    });

                    store.changeFocusFromHistory({
                        entry: first_entry,
                        key: "ArrowDown",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(another_entry);
                });

                it("goes around if the current is the first in the list", () => {
                    const first_entry = { html_url: "/first", title: "a" } as ItemEntry;
                    const another_entry = { html_url: "/another", title: "b" } as ItemEntry;

                    const store = useSwitchToStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                        programmatically_focused_element: another_entry,
                    });

                    store.changeFocusFromHistory({
                        entry: another_entry,
                        key: "ArrowDown",
                    });

                    expect(store.programmatically_focused_element).toStrictEqual(first_entry);
                });
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
                            { title: "Acme" } as ItemEntry,
                            { title: "ACME Corp" } as ItemEntry,
                            { title: "Another entry" } as ItemEntry,
                            { xref: "wiki #ACME" } as ItemEntry,
                        ],
                    },
                    filter_value: "acme",
                });

                expect(store.filtered_history).toStrictEqual({
                    entries: [
                        { title: "Acme" } as ItemEntry,
                        { title: "ACME Corp" } as ItemEntry,
                        { xref: "wiki #ACME" } as ItemEntry,
                    ],
                });
            });
        });
    });
});
