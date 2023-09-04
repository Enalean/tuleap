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

import type { Project, ItemDefinition, QuickLink } from "../type";
import { useRootStore } from "./root";
import { useKeyboardNavigationStore } from "./keyboard-navigation";
import type { State } from "./type";

describe("Keyboard navigation store", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe("Actions", () => {
        describe("changeFocusFromQuickLink", () => {
            describe("When user hits ArrowDown", () => {
                it("should focus the next item", () => {
                    const first_quick_link = { html_url: "/a" } as QuickLink;
                    const second_quick_link = { html_url: "/b" } as QuickLink;
                    const entry = {
                        html_url: "/first",
                        title: "a",
                        quick_links: [
                            first_quick_link,
                            second_quick_link,
                        ] as ReadonlyArray<QuickLink>,
                    } as ItemDefinition;
                    const next_entry = {
                        html_url: "/second",
                        title: "b",
                        quick_links: [] as ReadonlyArray<QuickLink>,
                    } as ItemDefinition;

                    const root_store = useRootStore();
                    root_store.$patch({
                        history: {
                            entries: [entry, next_entry],
                        },
                        projects: [{ project_uri: "/a", project_name: "a" } as Project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: first_quick_link,
                    });

                    navigation_store.changeFocusFromQuickLink({
                        project: null,
                        item: entry,
                        quick_link: first_quick_link,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        next_entry,
                    );
                });

                it("should focus the next project", () => {
                    const admin_quick_link = { html_url: "/admin" } as QuickLink;
                    const entry = { html_url: "/first", title: "a" } as ItemDefinition;

                    const project_a = {
                        project_name: "a",
                        project_uri: "/a",
                        quick_links: [admin_quick_link],
                    } as Project;
                    const project_b = {
                        project_name: "b",
                        project_uri: "/b",
                        quick_links: [] as QuickLink[],
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        is_history_loaded: true,
                        is_history_in_error: false,
                        history: {
                            entries: [entry],
                        },
                        projects: [project_a, project_b],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: admin_quick_link,
                    });

                    navigation_store.changeFocusFromQuickLink({
                        project: project_a,
                        item: null,
                        quick_link: admin_quick_link,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        project_b,
                    );
                });
            });

            describe("When user hits ArrowUp", () => {
                it("should focus the previous item", () => {
                    const first_quick_link = { html_url: "/a" } as QuickLink;
                    const second_quick_link = { html_url: "/b" } as QuickLink;
                    const entry = {
                        html_url: "/first",
                        title: "a",
                        quick_links: [
                            first_quick_link,
                            second_quick_link,
                        ] as ReadonlyArray<QuickLink>,
                    } as ItemDefinition;
                    const previous_entry = {
                        html_url: "/second",
                        title: "b",
                        quick_links: [] as ReadonlyArray<QuickLink>,
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [previous_entry, entry],
                        },
                        projects: [{ project_uri: "/a", project_name: "a" } as Project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: first_quick_link,
                    });

                    navigation_store.changeFocusFromQuickLink({
                        project: null,
                        item: entry,
                        quick_link: first_quick_link,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        previous_entry,
                    );
                });

                it("should focus the previous project", () => {
                    const admin_quick_link = { html_url: "/admin" } as QuickLink;
                    const entry = { html_url: "/first", title: "a" } as ItemDefinition;

                    const project_a = {
                        project_name: "a",
                        project_uri: "/a",
                        quick_links: [] as QuickLink[],
                    } as Project;
                    const project_b = {
                        project_name: "b",
                        project_uri: "/b",
                        quick_links: [admin_quick_link],
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        is_history_loaded: true,
                        is_history_in_error: false,
                        history: {
                            entries: [entry],
                        },
                        projects: [project_a, project_b],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: admin_quick_link,
                    });

                    navigation_store.changeFocusFromQuickLink({
                        project: project_b,
                        item: null,
                        quick_link: admin_quick_link,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        project_a,
                    );
                });
            });

            describe("When user hits ArrowRight", () => {
                it("should focus next quick link", () => {
                    const first_quick_link = { html_url: "/a" } as QuickLink;
                    const second_quick_link = { html_url: "/b" } as QuickLink;
                    const entry = {
                        html_url: "/first",
                        title: "a",
                        quick_links: [
                            first_quick_link,
                            second_quick_link,
                        ] as ReadonlyArray<QuickLink>,
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        projects: [{ project_uri: "/a", project_name: "a" } as Project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: first_quick_link,
                    });

                    navigation_store.changeFocusFromQuickLink({
                        project: null,
                        item: entry,
                        quick_link: first_quick_link,
                        key: "ArrowRight",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        second_quick_link,
                    );
                });

                describe("when we are already on the last quick link", () => {
                    it("should do nothing if we are on a recent item", () => {
                        const first_quick_link = { html_url: "/a" } as QuickLink;
                        const second_quick_link = { html_url: "/b" } as QuickLink;
                        const entry = {
                            html_url: "/first",
                            title: "a",
                            quick_links: [
                                first_quick_link,
                                second_quick_link,
                            ] as ReadonlyArray<QuickLink>,
                        } as ItemDefinition;

                        const store = useRootStore();
                        store.$patch({
                            history: {
                                entries: [entry],
                            },
                            projects: [{ project_uri: "/a", project_name: "a" } as Project],
                        });
                        const navigation_store = useKeyboardNavigationStore();
                        navigation_store.$patch({
                            programmatically_focused_element: second_quick_link,
                        });

                        navigation_store.changeFocusFromQuickLink({
                            project: null,
                            item: entry,
                            quick_link: second_quick_link,
                            key: "ArrowRight",
                        });

                        expect(navigation_store.programmatically_focused_element).toStrictEqual(
                            second_quick_link,
                        );
                    });

                    it("should focus on recent items if we are on a project", () => {
                        const admin_quick_link = { html_url: "/admin" } as QuickLink;
                        const first_entry = { html_url: "/first", title: "a" } as ItemDefinition;
                        const another_entry = {
                            html_url: "/another",
                            title: "b",
                        } as ItemDefinition;

                        const store = useRootStore();
                        store.$patch({
                            is_history_loaded: true,
                            is_history_in_error: false,
                            history: {
                                entries: [first_entry, another_entry],
                            },
                        });
                        const navigation_store = useKeyboardNavigationStore();
                        navigation_store.$patch({
                            programmatically_focused_element: admin_quick_link,
                        });

                        navigation_store.changeFocusFromQuickLink({
                            project: { quick_links: [admin_quick_link] } as Project,
                            item: null,
                            quick_link: admin_quick_link,
                            key: "ArrowRight",
                        });

                        expect(navigation_store.programmatically_focused_element).toStrictEqual(
                            first_entry,
                        );
                    });

                    it("should not focus on recent items if we are on a project but we started to search something", () => {
                        const admin_quick_link = { html_url: "/admin" } as QuickLink;
                        const first_entry = {
                            html_url: "/first",
                            title: "lorem a",
                        } as ItemDefinition;
                        const another_entry = {
                            html_url: "/another",
                            title: "lorem b",
                        } as ItemDefinition;

                        const store = useRootStore();
                        store.$patch({
                            is_history_loaded: true,
                            is_history_in_error: false,
                            history: {
                                entries: [first_entry, another_entry],
                            },
                            filter_value: "lorem",
                        });
                        const navigation_store = useKeyboardNavigationStore();
                        navigation_store.$patch({
                            programmatically_focused_element: admin_quick_link,
                        });

                        navigation_store.changeFocusFromQuickLink({
                            project: { quick_links: [admin_quick_link] } as Project,
                            item: null,
                            quick_link: admin_quick_link,
                            key: "ArrowRight",
                        });

                        expect(navigation_store.programmatically_focused_element).toStrictEqual(
                            admin_quick_link,
                        );
                    });
                });
            });

            describe("When user hits ArrowLeft", () => {
                it("should focus previous quick link", () => {
                    const first_quick_link = { html_url: "/a" } as QuickLink;
                    const second_quick_link = { html_url: "/b" } as QuickLink;
                    const entry = {
                        html_url: "/first",
                        title: "a",
                        quick_links: [
                            first_quick_link,
                            second_quick_link,
                        ] as ReadonlyArray<QuickLink>,
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        projects: [{ project_uri: "/a", project_name: "a" } as Project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: second_quick_link,
                    });

                    navigation_store.changeFocusFromQuickLink({
                        project: null,
                        item: entry,
                        quick_link: second_quick_link,
                        key: "ArrowLeft",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        first_quick_link,
                    );
                });

                describe("when we are already on the first quick link", () => {
                    it("should focus the project if we are on a project", () => {
                        const admin_quick_link = { html_url: "/admin" } as QuickLink;
                        const first_entry = { html_url: "/first", title: "a" } as ItemDefinition;
                        const another_entry = {
                            html_url: "/another",
                            title: "b",
                        } as ItemDefinition;

                        const store = useRootStore();
                        store.$patch({
                            is_history_loaded: true,
                            is_history_in_error: false,
                            history: {
                                entries: [first_entry, another_entry],
                            },
                        });
                        const navigation_store = useKeyboardNavigationStore();
                        navigation_store.$patch({
                            programmatically_focused_element: admin_quick_link,
                        });

                        const project = { quick_links: [admin_quick_link] } as Project;
                        navigation_store.changeFocusFromQuickLink({
                            project: project,
                            item: null,
                            quick_link: admin_quick_link,
                            key: "ArrowLeft",
                        });

                        expect(navigation_store.programmatically_focused_element).toStrictEqual(
                            project,
                        );
                    });

                    it("should focus on the recent item if we are on a recent item", () => {
                        const first_quick_link = { html_url: "/a" } as QuickLink;
                        const second_quick_link = { html_url: "/b" } as QuickLink;
                        const entry = {
                            html_url: "/first",
                            title: "a",
                            quick_links: [
                                first_quick_link,
                                second_quick_link,
                            ] as ReadonlyArray<QuickLink>,
                        } as ItemDefinition;

                        const store = useRootStore();
                        store.$patch({
                            history: {
                                entries: [entry],
                            },
                            projects: [{ project_uri: "/a", project_name: "a" } as Project],
                        });
                        const navigation_store = useKeyboardNavigationStore();
                        navigation_store.$patch({
                            programmatically_focused_element: first_quick_link,
                        });

                        navigation_store.changeFocusFromQuickLink({
                            project: null,
                            item: entry,
                            quick_link: first_quick_link,
                            key: "ArrowLeft",
                        });

                        expect(navigation_store.programmatically_focused_element).toStrictEqual(
                            entry,
                        );
                    });
                });
            });
        });

        describe("changeFocusFromProject", () => {
            it("does nothing if user hits left key", () => {
                const project = {} as Project;

                const store = useRootStore();
                store.$patch({
                    is_history_loaded: true,
                    is_history_in_error: false,
                    history: {
                        entries: [{ html_url: "/first", title: "a" } as ItemDefinition],
                    },
                });
                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: project,
                });

                navigation_store.changeFocusFromProject({
                    project,
                    key: "ArrowLeft",
                });

                expect(navigation_store.programmatically_focused_element).toStrictEqual(project);
            });

            describe("When user hits ArrowRight", () => {
                describe("on project user is admin", () => {
                    it("should focus the admin icon", () => {
                        const admin_link: QuickLink = {} as QuickLink;

                        const project = {
                            quick_links: [admin_link],
                        } as Project;

                        const store = useRootStore();
                        store.$patch({});
                        const navigation_store = useKeyboardNavigationStore();
                        navigation_store.$patch({
                            programmatically_focused_element: project,
                        });

                        navigation_store.changeFocusFromProject({
                            project,
                            key: "ArrowRight",
                        });

                        expect(navigation_store.programmatically_focused_element).toStrictEqual(
                            admin_link,
                        );
                    });
                });

                describe("on project user is not admin", () => {
                    it.each<[string, Partial<State>]>([
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
                        [
                            "the history is not empty but we started to search for something",
                            {
                                is_history_loaded: true,
                                is_history_in_error: false,
                                history: {
                                    entries: [{ title: "lorem" } as ItemDefinition],
                                },
                                filter_value: "lorem",
                            },
                        ],
                    ])("does nothing if %s", (description, partial_state) => {
                        const project = {
                            project_name: "lorem ipsum project",
                            quick_links: [] as QuickLink[],
                        } as Project;

                        const store = useRootStore();
                        store.$patch(partial_state);
                        const navigation_store = useKeyboardNavigationStore();
                        navigation_store.$patch({
                            programmatically_focused_element: project,
                        });

                        navigation_store.changeFocusFromProject({
                            project,
                            key: "ArrowRight",
                        });

                        expect(navigation_store.programmatically_focused_element).toStrictEqual(
                            project,
                        );
                    });

                    it("focus the first history entry", () => {
                        const first_entry = { html_url: "/first", title: "a" } as ItemDefinition;
                        const another_entry = {
                            html_url: "/another",
                            title: "b",
                        } as ItemDefinition;

                        const store = useRootStore();
                        store.$patch({
                            is_history_loaded: true,
                            is_history_in_error: false,
                            history: {
                                entries: [first_entry, another_entry],
                            },
                        });

                        const navigation_store = useKeyboardNavigationStore();
                        navigation_store.changeFocusFromProject({
                            project: { quick_links: [] as QuickLink[] } as Project,
                            key: "ArrowRight",
                        });

                        expect(navigation_store.programmatically_focused_element).toStrictEqual(
                            first_entry,
                        );
                    });
                });
            });

            describe("When user hits ArrowUp", () => {
                it("focus the filter input if the project list is empty", () => {
                    const project = {
                        project_name: "Guinea Pig",
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        projects: [],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: project,
                    });

                    navigation_store.changeFocusFromProject({
                        project,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toBeNull();
                });

                it("focus the filter input if the project list contains only one element", () => {
                    const project = {
                        project_name: "Guinea Pig",
                        project_uri: "/guinea-pig",
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        projects: [project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: project,
                    });

                    navigation_store.changeFocusFromProject({
                        project,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toBeNull();
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

                    const store = useRootStore();
                    store.$patch({
                        projects: [first_project, another_project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: another_project,
                    });

                    navigation_store.changeFocusFromProject({
                        project: another_project,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        first_project,
                    );
                });

                it("focus the filter input if the first project has already the focus", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "First",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "Another",
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        projects: [first_project, another_project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: first_project,
                    });

                    navigation_store.changeFocusFromProject({
                        project: first_project,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toBeNull();
                });
            });

            describe("When user hits ArrowDown", () => {
                it("does nothing if the project list is empty", () => {
                    const project = {
                        project_name: "Guinea Pig",
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        projects: [],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: project,
                    });

                    navigation_store.changeFocusFromProject({
                        project,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        project,
                    );
                });

                it("does nothing if the project list contains only one element", () => {
                    const project = {
                        project_name: "Guinea Pig",
                        project_uri: "/guinea-pig",
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        projects: [project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: project,
                    });

                    navigation_store.changeFocusFromProject({
                        project,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        project,
                    );
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

                    const store = useRootStore();
                    store.$patch({
                        projects: [first_project, another_project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: another_project,
                    });

                    navigation_store.changeFocusFromProject({
                        project: first_project,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        another_project,
                    );
                });

                it("goes down even when we are searching for something", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "First lorem",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "Another lorem",
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        projects: [first_project, another_project],
                        filter_value: "lorem",
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: first_project,
                    });

                    navigation_store.changeFocusFromProject({
                        project: first_project,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        another_project,
                    );
                });

                it("does nothing if the last project has already the focus", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "First",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "Another",
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        projects: [first_project, another_project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: another_project,
                    });

                    navigation_store.changeFocusFromProject({
                        project: another_project,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        another_project,
                    );
                });

                it("should focus the first recent item if the last project has already the focus and we are searching for something", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "lorem First",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "lorem Another",
                    } as Project;

                    const first_entry = {
                        html_url: "/first",
                        title: "lorem a",
                    } as ItemDefinition;
                    const another_entry = {
                        html_url: "/another",
                        title: "lorem b",
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        is_history_loaded: true,
                        is_history_in_error: false,
                        history: {
                            entries: [first_entry, another_entry],
                        },
                        projects: [first_project, another_project],
                        filter_value: "lorem",
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: another_project,
                    });

                    navigation_store.changeFocusFromProject({
                        project: another_project,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        first_entry,
                    );
                });

                it("should focus the first search result if the last project has already the focus and we are searching for something and there is no recent item", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "lorem First",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "lorem Another",
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        is_history_loaded: true,
                        is_history_in_error: false,
                        history: {
                            entries: [],
                        },
                        projects: [first_project, another_project],
                        filter_value: "lorem",
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: another_project,
                    });

                    navigation_store.changeFocusFromProject({
                        project: another_project,
                        key: "ArrowDown",
                    });

                    expect(focusFirstSearchResult).toHaveBeenCalled();
                });
            });
        });

        describe("changeFocusFromHistory", () => {
            describe("When user hits ArrowRight", () => {
                it("does nothing if item has no quick link", () => {
                    const entry = {
                        html_url: "/first",
                        title: "a",
                        quick_links: [] as ReadonlyArray<QuickLink>,
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        projects: [{ project_uri: "/a", project_name: "a" } as Project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry,
                        key: "ArrowRight",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("should focus on first quick link", () => {
                    const first_quick_link = {} as QuickLink;
                    const second_quick_link = {} as QuickLink;
                    const entry = {
                        html_url: "/first",
                        title: "a",
                        quick_links: [
                            first_quick_link,
                            second_quick_link,
                        ] as ReadonlyArray<QuickLink>,
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        projects: [{ project_uri: "/a", project_name: "a" } as Project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry,
                        key: "ArrowRight",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        first_quick_link,
                    );
                });
            });

            describe("When user hits ArrowLeft", () => {
                it("does nothing if the project list is empty", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        projects: [],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry,
                        key: "ArrowLeft",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("focus the first project", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemDefinition;

                    const first_project = { project_uri: "/a", project_name: "a" } as Project;
                    const another_project = { project_uri: "/b", project_name: "b" } as Project;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        projects: [first_project, another_project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry,
                        key: "ArrowLeft",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        first_project,
                    );
                });

                it("should not focus the first project if we started to search for something", () => {
                    const entry = { html_url: "/first", title: "lorem a" } as ItemDefinition;

                    const first_project = { project_uri: "/a", project_name: "lorem a" } as Project;
                    const another_project = {
                        project_uri: "/b",
                        project_name: "lorem b",
                    } as Project;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                        filter_value: "lorem",
                        projects: [first_project, another_project],
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry,
                        key: "ArrowLeft",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(entry);
                });
            });

            describe("When user hits ArrowUp", () => {
                it("does nothing if the history is empty", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [],
                        },
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("does nothing if the history contains only one element", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("goes up", () => {
                    const first_entry = { html_url: "/first", title: "a" } as ItemDefinition;
                    const another_entry = { html_url: "/another", title: "b" } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: another_entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry: another_entry,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        first_entry,
                    );
                });

                it("goes up even when we are searching for something", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "First lorem",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "Another lorem",
                    } as Project;

                    const first_entry = {
                        html_url: "/first-entry",
                        title: "a lorem",
                    } as ItemDefinition;
                    const another_entry = {
                        html_url: "/another-entry",
                        title: "b lorem",
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                        projects: [first_project, another_project],
                        filter_value: "lorem",
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: another_entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry: another_entry,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        first_entry,
                    );
                });

                it("does nothing if the first recent item has already the focus", () => {
                    const first_entry = { html_url: "/first", title: "a" } as ItemDefinition;
                    const another_entry = { html_url: "/another", title: "b" } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: first_entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry: first_entry,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        first_entry,
                    );
                });

                it("should focus the last project if the first recent item has already the focus and we are searching for something", () => {
                    const first_project = {
                        project_uri: "/first",
                        project_name: "First lorem",
                    } as Project;
                    const another_project = {
                        project_uri: "/another",
                        project_name: "Another lorem",
                    } as Project;

                    const first_entry = {
                        html_url: "/first-entry",
                        title: "a lorem",
                    } as ItemDefinition;
                    const another_entry = {
                        html_url: "/another-entry",
                        title: "b lorem",
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                        projects: [first_project, another_project],
                        filter_value: "lorem",
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: first_entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry: first_entry,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        another_project,
                    );
                });

                it("should focus the filter input if the first recent item has already the focus and we are searching for something and there is no project", () => {
                    const first_entry = {
                        html_url: "/first-entry",
                        title: "a lorem",
                    } as ItemDefinition;
                    const another_entry = {
                        html_url: "/another-entry",
                        title: "b lorem",
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                        projects: [],
                        filter_value: "lorem",
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: first_entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry: first_entry,
                        key: "ArrowUp",
                    });

                    expect(navigation_store.programmatically_focused_element).toBeNull();
                });
            });

            describe("When user hits ArrowDown", () => {
                it("does nothing if the history is empty", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [],
                        },
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("does nothing if the history contains only one element", () => {
                    const entry = { html_url: "/first", title: "a" } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [entry],
                        },
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(entry);
                });

                it("goes down", () => {
                    const first_entry = { html_url: "/first", title: "a" } as ItemDefinition;
                    const another_entry = { html_url: "/another", title: "b" } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: first_entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry: first_entry,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        another_entry,
                    );
                });

                it("does nothing if the last recent item has already the focus", () => {
                    const first_entry = { html_url: "/first", title: "a" } as ItemDefinition;
                    const another_entry = { html_url: "/another", title: "b" } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: another_entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry: another_entry,
                        key: "ArrowDown",
                    });

                    expect(navigation_store.programmatically_focused_element).toStrictEqual(
                        another_entry,
                    );
                });

                it("should focus the first search result if the last recent item has already the focus and we are searching for something", () => {
                    const first_entry = { html_url: "/first", title: "a lorem" } as ItemDefinition;
                    const another_entry = {
                        html_url: "/another",
                        title: "b lorem",
                    } as ItemDefinition;

                    const store = useRootStore();
                    store.$patch({
                        history: {
                            entries: [first_entry, another_entry],
                        },
                        filter_value: "lorem",
                    });
                    const navigation_store = useKeyboardNavigationStore();
                    navigation_store.$patch({
                        programmatically_focused_element: another_entry,
                    });

                    navigation_store.changeFocusFromHistory({
                        entry: another_entry,
                        key: "ArrowDown",
                    });

                    expect(focusFirstSearchResult).toHaveBeenCalled();
                });
            });
        });

        describe("setProgrammaticallyFocusedElement", () => {
            it("should store the new focused element", () => {
                const store = useKeyboardNavigationStore();
                store.$patch({
                    programmatically_focused_element: null,
                });

                const quick_link = { html_url: "/nous-c-est-le-gout" } as QuickLink;

                store.setProgrammaticallyFocusedElement(quick_link);

                expect(store.programmatically_focused_element).toStrictEqual(quick_link);
            });
        });

        describe("changeFocusFromFilterInput", () => {
            it("should focus the first project", () => {
                const first_project = {
                    project_uri: "/first",
                    project_name: "First lorem",
                } as Project;
                const another_project = {
                    project_uri: "/another",
                    project_name: "Another lorem",
                } as Project;

                const first_entry = {
                    html_url: "/first-entry",
                    title: "a lorem",
                } as ItemDefinition;
                const another_entry = {
                    html_url: "/another-entry",
                    title: "b lorem",
                } as ItemDefinition;

                const store = useRootStore();
                store.$patch({
                    history: {
                        entries: [first_entry, another_entry],
                    },
                    projects: [first_project, another_project],
                    filter_value: "lorem",
                });
                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: null,
                });

                navigation_store.changeFocusFromFilterInput();

                expect(navigation_store.programmatically_focused_element).toStrictEqual(
                    first_project,
                );
            });

            it("should focus the first recent item if there is no project", () => {
                const first_entry = {
                    html_url: "/first-entry",
                    title: "a lorem",
                } as ItemDefinition;
                const another_entry = {
                    html_url: "/another-entry",
                    title: "b lorem",
                } as ItemDefinition;

                const store = useRootStore();
                store.$patch({
                    history: {
                        entries: [first_entry, another_entry],
                    },
                    projects: [],
                    filter_value: "lorem",
                    is_history_loaded: true,
                });
                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: null,
                });

                navigation_store.changeFocusFromFilterInput();

                expect(navigation_store.programmatically_focused_element).toStrictEqual(
                    first_entry,
                );
            });

            it("should focus the first search result if there is no project and there is no recent item", () => {
                const store = useRootStore();
                store.$patch({
                    history: {
                        entries: [],
                    },
                    projects: [],
                    filter_value: "lorem",
                    is_history_loaded: true,
                });
                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: null,
                });

                navigation_store.changeFocusFromFilterInput();

                expect(focusFirstSearchResult).toHaveBeenCalled();
            });
        });
    });
});
