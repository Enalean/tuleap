/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

import store from "./global";
import * as rest_querier from "../api/rest-querier";

describe("Global store:", () => {
    let context;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            dispatch: jest.fn(),
            getters: {
                findArtifactById: jest.fn(),
                findBaselineById: jest.fn(),
            },
        };
        context.dispatch.mockReturnValue(Promise.resolve());
    });

    describe("actions", () => {
        describe("#loadBaselines", () => {
            beforeEach(() => {
                context.getters.findBaselineById.mockImplementation((id) => {
                    if (id === 1) {
                        return { artifact_id: 10 };
                    }
                    if (id === 2) {
                        return { artifact_id: 20 };
                    }
                    throw new Error("Not expected ID: " + id);
                });

                return store.actions.loadBaselines(context, { baseline_ids: [1, 2] });
            });

            it("dispatches 'loadBaseline' for each baseline id", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadBaseline", { baseline_id: 1 });
                expect(context.dispatch).toHaveBeenCalledWith("loadBaseline", { baseline_id: 2 });
            });

            it("dispatches 'loadTrackers'", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadArtifacts", {
                    artifact_ids: [10, 20],
                });
            });

            describe("when all given ids are identical", () => {
                beforeEach(() => {
                    context.dispatch.mockClear();
                    return store.actions.loadBaselines(context, { baseline_ids: [1, 1] });
                });

                it("dispatches 'loadBaseline' once", () => {
                    const loadBaseline_calls = context.dispatch.mock.calls.filter(
                        (call) => call[0] === "loadBaseline",
                    );
                    expect(loadBaseline_calls).toHaveLength(1);
                });
            });
        });

        describe("#loadBaseline", () => {
            const user = { id: 1 };

            beforeEach(() => {
                jest.spyOn(rest_querier, "getBaseline").mockReturnValue(Promise.resolve(user));

                return store.actions.loadBaseline(context, { baseline_id: 1 });
            });

            it("commits 'addBaseline' with fetch user", () => {
                expect(context.commit).toHaveBeenCalledWith("addBaseline", user);
            });
        });

        describe("#loadUsers", () => {
            beforeEach(() => store.actions.loadUsers(context, { user_ids: [1, 2] }));

            it("dispatches 'loadUser' for each user id", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadUser", { user_id: 1 });
                expect(context.dispatch).toHaveBeenCalledWith("loadUser", { user_id: 2 });
            });

            describe("when all given ids are identical", () => {
                beforeEach(() => {
                    context.dispatch.mockClear();
                    return store.actions.loadUsers(context, { user_ids: [1, 1] });
                });

                it("dispatches once", () => {
                    expect(context.dispatch).toHaveBeenCalledTimes(1);
                });
            });
        });

        describe("#loadUser", () => {
            const user = { id: 1 };

            beforeEach(() => {
                jest.spyOn(rest_querier, "getUser").mockReturnValue(Promise.resolve(user));

                return store.actions.loadUser(context, { user_id: 1 });
            });

            it("commits 'addUser' with fetch user", () => {
                expect(context.commit).toHaveBeenCalledWith("addUser", user);
            });
        });

        describe("#loadArtifacts", () => {
            beforeEach(() => {
                context.getters.findArtifactById.mockImplementation((id) => {
                    if (id === 1) {
                        return { id: 1, tracker: { id: 10 } };
                    }
                    if (id === 2) {
                        return { id: 2, tracker: { id: 20 } };
                    }
                    throw new Error("Not expected ID: " + id);
                });

                return store.actions.loadArtifacts(context, { artifact_ids: [1, 2] });
            });

            it("dispatches 'loadArtifact' for each artifact id", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadArtifact", { artifact_id: 1 });
                expect(context.dispatch).toHaveBeenCalledWith("loadArtifact", { artifact_id: 2 });
            });

            it("dispatches 'loadTrackers'", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadTrackers", {
                    tracker_ids: [10, 20],
                });
            });

            describe("when all given ids are identical", () => {
                beforeEach(() => {
                    context.dispatch.mockClear();
                    return store.actions.loadArtifacts(context, { artifact_ids: [1, 1] });
                });

                it("dispatches 'loadArtifact' once", () => {
                    expect(context.dispatch.mock.calls).toEqual([
                        ["loadArtifact", expect.any(Object)],
                        ["loadTrackers", expect.any(Object)],
                    ]);
                });
            });
        });

        describe("#loadArtifact", () => {
            const artifact = { id: 1 };

            beforeEach(() => {
                jest.spyOn(rest_querier, "getArtifact").mockReturnValue(Promise.resolve(artifact));

                return store.actions.loadArtifact(context, { artifact_id: 1 });
            });

            it("commits 'addArtifact' with fetch artifact", () => {
                expect(context.commit).toHaveBeenCalledWith("addArtifact", artifact);
            });
        });

        describe("#loadTrackers", () => {
            beforeEach(() => store.actions.loadTrackers(context, { tracker_ids: [1, 2] }));

            it("dispatches 'loadTracker' for each tracker id", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadTracker", { tracker_id: 1 });
                expect(context.dispatch).toHaveBeenCalledWith("loadTracker", { tracker_id: 2 });
            });

            describe("when all given ids are identical", () => {
                beforeEach(() => {
                    context.dispatch.mockClear();
                    return store.actions.loadTrackers(context, { tracker_ids: [1, 1] });
                });

                it("dispatches once", () => {
                    expect(context.dispatch).toHaveBeenCalledTimes(1);
                });
            });
        });

        describe("#loadTracker", () => {
            const tracker = { id: 9 };

            beforeEach(() => {
                jest.spyOn(rest_querier, "getTracker").mockReturnValue(Promise.resolve(tracker));

                return store.actions.loadTracker(context, { tracker_id: 1 });
            });

            it("commits 'addTracker' with fetch tracker", () => {
                expect(context.commit).toHaveBeenCalledWith("addTracker", tracker);
            });
        });
    });

    describe("mutations", () => {
        const state = { ...store.state };

        describe("#addUser", () => {
            const user = { id: 1 };
            beforeEach(() => store.mutations.addUser(state, user));

            it("add given user with corresponding id", () => {
                expect(state.users_by_id[1]).toEqual(user);
            });
        });

        describe("#addArtifact", () => {
            const artifact = { id: 1 };
            beforeEach(() => store.mutations.addArtifact(state, artifact));

            it("add given artifact with corresponding id", () => {
                expect(state.artifacts_by_id[1]).toEqual(artifact);
            });
        });

        describe("#addTracker", () => {
            const tracker = { id: 1 };
            beforeEach(() => store.mutations.addTracker(state, tracker));

            it("add given tracker with corresponding id", () => {
                expect(state.trackers_by_id[1]).toEqual(tracker);
            });
        });
    });

    describe("getters", () => {
        let state = { ...store.state };

        describe("#findUserById", () => {
            const user = { id: 1 };
            beforeEach(
                () =>
                    (state.users_by_id = {
                        1: user,
                    }),
            );

            it("returns user with given id", () => {
                expect(store.getters.findUserById(state)(1)).toEqual(user);
            });
        });

        describe("#findArtifactById", () => {
            const artifact = { id: 1 };
            beforeEach(
                () =>
                    (state.artifacts_by_id = {
                        1: artifact,
                    }),
            );

            it("returns artifact with given id", () => {
                expect(store.getters.findArtifactById(state)(1)).toEqual(artifact);
            });
        });

        describe("#findTrackerById", () => {
            const tracker = { id: 1 };
            beforeEach(
                () =>
                    (state.trackers_by_id = {
                        1: tracker,
                    }),
            );

            it("returns tracker with given id", () => {
                expect(store.getters.findTrackerById(state)(1)).toEqual(tracker);
            });
        });
    });
});
