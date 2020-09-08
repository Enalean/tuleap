/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import * as actions from "./actions";
import { State } from "./type";
import {
    mockFetchError,
    mockFetchSuccess,
} from "../../../../themes/tlp/mocks/tlp-fetch-mock-helper";
import * as tlp from "../../../../themes/tlp/src/js/fetch-wrapper";
import { ActionContext } from "vuex";
import { Project, UserHistory, UserHistoryEntry } from "../type";

jest.mock("tlp");

describe("SwitchTo actions", () => {
    describe("loadHistory", () => {
        it("Rethrow API error", async () => {
            const tlpGetMock = jest.spyOn(tlp, "get");

            mockFetchError(tlpGetMock, {});

            const context = ({
                commit: jest.fn(),
                dispatch: jest.fn(),
                state: {
                    user_id: 102,
                    is_history_loaded: false,
                } as State,
            } as unknown) as ActionContext<State, State>;

            await expect(actions.loadHistory(context)).rejects.toBeDefined();

            expect(context.commit).toHaveBeenCalledWith("setErrorForHistory", true);
        });

        it("Fetch user history", async () => {
            const tlpGetMock = jest.spyOn(tlp, "get");

            mockFetchSuccess(tlpGetMock, {
                return_json: {
                    entries: [{ xref: "art #1" }],
                },
            });

            const context = ({
                commit: jest.fn(),
                dispatch: jest.fn(),
                state: {
                    user_id: 102,
                    is_history_loaded: false,
                } as State,
            } as unknown) as ActionContext<State, State>;

            await actions.loadHistory(context);

            expect(context.commit).toHaveBeenCalledWith("saveHistory", {
                entries: [{ xref: "art #1" }],
            });
        });

        it("Does not fetch user history if it has already been loaded", async () => {
            const tlpGetMock = jest.spyOn(tlp, "get");

            const context = ({
                commit: jest.fn(),
                dispatch: jest.fn(),
                state: {
                    user_id: 102,
                    is_history_loaded: true,
                } as State,
            } as unknown) as ActionContext<State, State>;

            await actions.loadHistory(context);

            expect(tlpGetMock).not.toHaveBeenCalled();
        });
    });

    describe("changeFocusFromProject", () => {
        it("does nothing if user hits left key", () => {
            const context = ({
                commit: jest.fn(),
            } as unknown) as ActionContext<State, State>;

            actions.changeFocusFromProject(context, {
                project: {} as Project,
                key: "ArrowLeft",
            });

            expect(context.commit).not.toHaveBeenCalled();
        });

        describe("When user hits ArrowRight", () => {
            it("does nothing if the history is not loaded yet", () => {
                const context = ({
                    commit: jest.fn(),
                    state: {
                        is_history_loaded: false,
                    } as State,
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: {} as Project,
                    key: "ArrowRight",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("does nothing if the history is in error", () => {
                const context = ({
                    commit: jest.fn(),
                    state: {
                        is_history_loaded: true,
                        is_history_in_error: true,
                    } as State,
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: {} as Project,
                    key: "ArrowRight",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("does nothing if the history is empty", () => {
                const context = ({
                    commit: jest.fn(),
                    state: {
                        is_history_loaded: true,
                        is_history_in_error: false,
                    } as State,
                    getters: {
                        filtered_history: { entries: [] } as UserHistory,
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: {} as Project,
                    key: "ArrowRight",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("focus the first history entry", () => {
                const first_project = { html_url: "/first" } as UserHistoryEntry;
                const another_project = { html_url: "/another" } as UserHistoryEntry;

                const context = ({
                    commit: jest.fn(),
                    state: {
                        is_history_loaded: true,
                        is_history_in_error: false,
                    } as State,
                    getters: {
                        filtered_history: {
                            entries: [first_project, another_project],
                        } as UserHistory,
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: {} as Project,
                    key: "ArrowRight",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    first_project
                );
            });
        });

        describe("When user hits ArrowUp", () => {
            it("does nothing if the project list is empty", () => {
                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_projects: [] as Project[],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: {} as Project,
                    key: "ArrowUp",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("does nothing if the project list contains only one element", () => {
                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_projects: [{} as Project],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: {} as Project,
                    key: "ArrowUp",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("goes up", () => {
                const first_project = { project_uri: "/first" } as Project;
                const another_project = { project_uri: "/another" } as Project;

                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_projects: [first_project, another_project],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: another_project,
                    key: "ArrowUp",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    first_project
                );
            });

            it("goes around if the current is the first in the list", () => {
                const first_project = { project_uri: "/first" } as Project;
                const another_project = { project_uri: "/another" } as Project;

                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_projects: [first_project, another_project],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: first_project,
                    key: "ArrowUp",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    another_project
                );
            });
        });

        describe("When user hits ArrowDown", () => {
            it("does nothing if the project list is empty", () => {
                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_projects: [] as Project[],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: {} as Project,
                    key: "ArrowDown",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("does nothing if the project list contains only one element", () => {
                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_projects: [{} as Project],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: {} as Project,
                    key: "ArrowDown",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("goes down", () => {
                const first_project = { project_uri: "/first" } as Project;
                const another_project = { project_uri: "/another" } as Project;

                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_projects: [first_project, another_project],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: first_project,
                    key: "ArrowDown",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    another_project
                );
            });

            it("goes around if the current is the first in the list", () => {
                const first_project = { project_uri: "/first" } as Project;
                const another_project = { project_uri: "/another" } as Project;

                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_projects: [first_project, another_project],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromProject(context, {
                    project: another_project,
                    key: "ArrowDown",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    first_project
                );
            });
        });
    });

    describe("changeFocusFromHistory", () => {
        it("does nothing if user hits right key", () => {
            const context = ({
                commit: jest.fn(),
            } as unknown) as ActionContext<State, State>;

            actions.changeFocusFromHistory(context, {
                entry: {} as UserHistoryEntry,
                key: "ArrowRight",
            });

            expect(context.commit).not.toHaveBeenCalled();
        });

        describe("When user hits ArrowLeft", () => {
            it("does nothing if the project list is empty", () => {
                const context = ({
                    commit: jest.fn(),
                    state: {} as State,
                    getters: {
                        filtered_projects: [] as Project[],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: {} as UserHistoryEntry,
                    key: "ArrowLeft",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("focus the first project", () => {
                const first_project = { project_uri: "/first" } as Project;
                const another_project = { project_uri: "/another" } as Project;

                const context = ({
                    commit: jest.fn(),
                    state: {} as State,
                    getters: {
                        filtered_projects: [first_project, another_project],
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: {} as UserHistoryEntry,
                    key: "ArrowLeft",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    first_project
                );
            });
        });

        describe("When user hits ArrowUp", () => {
            it("does nothing if the history is empty", () => {
                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_history: { entries: [] as UserHistoryEntry[] },
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: {} as UserHistoryEntry,
                    key: "ArrowUp",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("does nothing if the history contains only one element", () => {
                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_history: { entries: [{} as UserHistoryEntry] },
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: {} as UserHistoryEntry,
                    key: "ArrowUp",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("goes up", () => {
                const first_entry = { html_url: "/first" } as UserHistoryEntry;
                const another_entry = { html_url: "/another" } as UserHistoryEntry;

                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_history: { entries: [first_entry, another_entry] },
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: another_entry,
                    key: "ArrowUp",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    first_entry
                );
            });

            it("goes around if the current is the first in the list", () => {
                const first_entry = { html_url: "/first" } as UserHistoryEntry;
                const another_entry = { html_url: "/another" } as UserHistoryEntry;

                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_history: { entries: [first_entry, another_entry] },
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: first_entry,
                    key: "ArrowUp",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    another_entry
                );
            });
        });

        describe("When user hits ArrowDown", () => {
            it("does nothing if the history is empty", () => {
                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_history: { entries: [] as UserHistoryEntry[] },
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: {} as UserHistoryEntry,
                    key: "ArrowDown",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("does nothing if the history contains only one element", () => {
                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_history: { entries: [{} as UserHistoryEntry] },
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: {} as UserHistoryEntry,
                    key: "ArrowDown",
                });

                expect(context.commit).not.toHaveBeenCalled();
            });

            it("goes down", () => {
                const first_entry = { html_url: "/first" } as UserHistoryEntry;
                const another_entry = { html_url: "/another" } as UserHistoryEntry;

                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_history: { entries: [first_entry, another_entry] },
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: first_entry,
                    key: "ArrowDown",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    another_entry
                );
            });

            it("goes around if the current is the first in the list", () => {
                const first_entry = { html_url: "/first" } as UserHistoryEntry;
                const another_entry = { html_url: "/another" } as UserHistoryEntry;

                const context = ({
                    commit: jest.fn(),
                    getters: {
                        filtered_history: { entries: [first_entry, another_entry] },
                    },
                } as unknown) as ActionContext<State, State>;

                actions.changeFocusFromHistory(context, {
                    entry: another_entry,
                    key: "ArrowDown",
                });

                expect(context.commit).toHaveBeenCalledWith(
                    "setProgrammaticallyFocusedElement",
                    first_entry
                );
            });
        });
    });
});
