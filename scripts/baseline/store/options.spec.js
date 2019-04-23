/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

import store from "./options";
import { create } from "../support/factories";
import {
    restore,
    rewire$getBaseline,
    rewire$getUser,
    rewire$getTracker,
    rewire$getArtifact
} from "../api/rest-querier";

describe("Global store:", () => {
    let context;

    beforeEach(() => {
        context = {
            commit: jasmine.createSpy("commit"),
            dispatch: jasmine.createSpy("dispatch"),
            getters: {
                findArtifactById: jasmine.createSpy("findArtifactById"),
                findBaselineById: jasmine.createSpy("findBaselineById")
            }
        };
        context.dispatch.and.returnValue(Promise.resolve());
    });

    afterEach(restore);

    describe("actions", () => {
        describe("#loadBaselines", () => {
            beforeEach(() => {
                context.getters.findBaselineById
                    .withArgs(1)
                    .and.returnValue(create("baseline", { artifact_id: 10 }))
                    .withArgs(2)
                    .and.returnValue(create("baseline", { artifact_id: 20 }));

                return store.actions.loadBaselines(context, { baseline_ids: [1, 2] });
            });

            it("dispatches 'loadBaseline' for each baseline id", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadBaseline", { baseline_id: 1 });
                expect(context.dispatch).toHaveBeenCalledWith("loadBaseline", { baseline_id: 2 });
            });

            it("dispatches 'loadTrackers'", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadArtifacts", {
                    artifact_ids: [10, 20]
                });
            });

            describe("when all given ids are identical", () => {
                beforeEach(() => {
                    context.dispatch.calls.reset();
                    return store.actions.loadBaselines(context, { baseline_ids: [1, 1] });
                });

                it("dispatches 'loadBaseline' once", () => {
                    const loadBaseline_calls = context.dispatch.calls
                        .all()
                        .filter(call => call.args[0] === "loadBaseline");
                    expect(loadBaseline_calls.length).toEqual(1);
                });
            });
        });

        describe("#loadBaseline", () => {
            let getBaseline;
            const user = create("user");

            beforeEach(() => {
                getBaseline = jasmine.createSpy("getBaseline");
                rewire$getBaseline(getBaseline);
                getBaseline.and.returnValue(Promise.resolve(user));

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
                    context.dispatch.calls.reset();
                    return store.actions.loadUsers(context, { user_ids: [1, 1] });
                });

                it("dispatches once", () => {
                    expect(context.dispatch).toHaveBeenCalledTimes(1);
                });
            });
        });

        describe("#loadUser", () => {
            let getUser;
            const user = create("user");

            beforeEach(() => {
                getUser = jasmine.createSpy("getUser");
                rewire$getUser(getUser);
                getUser.and.returnValue(Promise.resolve(user));

                return store.actions.loadUser(context, { user_id: 1 });
            });

            it("commits 'addUser' with fetch user", () => {
                expect(context.commit).toHaveBeenCalledWith("addUser", user);
            });
        });

        describe("#loadArtifacts", () => {
            beforeEach(() => {
                context.getters.findArtifactById
                    .withArgs(1)
                    .and.returnValue(create("artifact", { tracker: { id: 10 } }))
                    .withArgs(2)
                    .and.returnValue(create("artifact", { tracker: { id: 20 } }));

                return store.actions.loadArtifacts(context, { artifact_ids: [1, 2] });
            });

            it("dispatches 'loadArtifact' for each artifact id", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadArtifact", { artifact_id: 1 });
                expect(context.dispatch).toHaveBeenCalledWith("loadArtifact", { artifact_id: 2 });
            });

            it("dispatches 'loadTrackers'", () => {
                expect(context.dispatch).toHaveBeenCalledWith("loadTrackers", {
                    tracker_ids: [10, 20]
                });
            });

            describe("when all given ids are identical", () => {
                beforeEach(() => {
                    context.dispatch.calls.reset();
                    return store.actions.loadArtifacts(context, { artifact_ids: [1, 1] });
                });

                it("dispatches 'loadArtifact' once", () => {
                    expect(context.dispatch.calls.allArgs()).toEqual([
                        ["loadArtifact", jasmine.any(Object)],
                        ["loadTrackers", jasmine.any(Object)]
                    ]);
                });
            });
        });

        describe("#loadArtifact", () => {
            let getArtifact;
            const artifact = create("artifact");

            beforeEach(() => {
                getArtifact = jasmine.createSpy("getArtifact");
                rewire$getArtifact(getArtifact);
                getArtifact.and.returnValue(Promise.resolve(artifact));

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
                    context.dispatch.calls.reset();
                    return store.actions.loadTrackers(context, { tracker_ids: [1, 1] });
                });

                it("dispatches once", () => {
                    expect(context.dispatch).toHaveBeenCalledTimes(1);
                });
            });
        });

        describe("#loadTracker", () => {
            let getTracker;
            const tracker = create("tracker");

            beforeEach(() => {
                getTracker = jasmine.createSpy("getTracker");
                rewire$getTracker(getTracker);
                getTracker.and.returnValue(Promise.resolve(tracker));

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
            const user = create("user", { id: 1 });
            beforeEach(() => store.mutations.addUser(state, user));

            it("add given user with corresponding id", () => {
                expect(state.users_by_id[1]).toEqual(user);
            });
        });

        describe("#addArtifact", () => {
            const artifact = create("artifact", { id: 1 });
            beforeEach(() => store.mutations.addArtifact(state, artifact));

            it("add given artifact with corresponding id", () => {
                expect(state.artifacts_by_id[1]).toEqual(artifact);
            });
        });

        describe("#addTracker", () => {
            const tracker = create("tracker", { id: 1 });
            beforeEach(() => store.mutations.addTracker(state, tracker));

            it("add given tracker with corresponding id", () => {
                expect(state.trackers_by_id[1]).toEqual(tracker);
            });
        });
    });

    describe("getters", () => {
        let state = { ...store.state };

        describe("#findUserById", () => {
            const user = create("user");
            beforeEach(() =>
                (state.users_by_id = {
                    1: user
                }));

            it("returns user with given id", () => {
                expect(store.getters.findUserById(state)(1)).toEqual(user);
            });
        });

        describe("#findArtifactById", () => {
            const artifact = create("artifact");
            beforeEach(() =>
                (state.artifacts_by_id = {
                    1: artifact
                }));

            it("returns artifact with given id", () => {
                expect(store.getters.findArtifactById(state)(1)).toEqual(artifact);
            });
        });

        describe("#findTrackerById", () => {
            const tracker = create("tracker");
            beforeEach(() =>
                (state.trackers_by_id = {
                    1: tracker
                }));

            it("returns tracker with given id", () => {
                expect(store.getters.findTrackerById(state)(1)).toEqual(tracker);
            });
        });
    });
});
