/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { mockFetchError, mockFetchSuccess } from "tlp-mocks";
import {
    createTransition,
    loadTracker,
    resetWorkflowTransitions,
    createWorkflowTransitions,
    updateTransitionRulesEnforcement,
    deleteTransition
} from "./actions.js";
import {
    restore as restore$RestQuerier,
    rewire$createTransition,
    rewire$createWorkflowTransitions,
    rewire$getTracker,
    rewire$resetWorkflowTransitions,
    rewire$updateTransitionRulesEnforcement,
    rewire$deleteTransition
} from "../api/rest-querier.js";
import { restore as restore$ExceptionHandler, rewire$getErrorMessage } from "./exceptionHandler.js";
import { create } from "../support/factories.js";

describe("Store actions:", () => {
    let context;
    let getErrorMessage;

    beforeEach(() => {
        context = {
            commit: jasmine.createSpy("commit"),
            state: {}
        };
        getErrorMessage = jasmine.createSpy("getErrorMessage");
        rewire$getErrorMessage(getErrorMessage);
    });

    afterEach(() => {
        restore$RestQuerier();
        restore$ExceptionHandler();
    });

    describe("loadTracker()", () => {
        let getTracker;
        beforeEach(() => {
            getTracker = jasmine.createSpy("getTracker");
            rewire$getTracker(getTracker);
        });

        it("fetches tracker asynchronously and store it as current tracker", async () => {
            const tracker = create("tracker");
            getTracker.and.returnValue(Promise.resolve(tracker));

            await loadTracker(context);

            expect(context.commit).toHaveBeenCalledWith("saveCurrentTracker", tracker);
            expect(context.commit).toHaveBeenCalledWith("stopCurrentTrackerLoading");
        });

        it("stores loading failure when server request fail", async () => {
            mockFetchError(getTracker, {});

            await loadTracker(context);

            expect(context.commit).toHaveBeenCalledWith("failCurrentTrackerLoading");
        });
    });

    describe("createWorkflowTransitions()", () => {
        let restCreateWorkflowTransitions;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 }
            };
            restCreateWorkflowTransitions = jasmine.createSpy("createWorkflowTransitions");
            rewire$createWorkflowTransitions(restCreateWorkflowTransitions);
        });

        describe("when workflow creation is successful", () => {
            beforeEach(async () => {
                mockFetchSuccess(restCreateWorkflowTransitions);
                await createWorkflowTransitions(context, 9);
            });

            it("begins a new operation", () =>
                expect(context.commit).toHaveBeenCalledWith("beginOperation"));
            it("creates workflow", () =>
                expect(restCreateWorkflowTransitions).toHaveBeenCalledWith(1, 9));
            it("creates workflow in store", () =>
                expect(context.commit).toHaveBeenCalledWith("createWorkflow", 9));
            it("ends operation", () => expect(context.commit).toHaveBeenCalledWith("endOperation"));
        });
    });

    describe("resetWorkflowTransitions()", () => {
        let restResetWorkflowTransitions;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 }
            };
            restResetWorkflowTransitions = jasmine.createSpy("resetWorkflowTransitions");
            rewire$resetWorkflowTransitions(restResetWorkflowTransitions);
        });

        describe("when reset workflow transitions is successful", () => {
            const tracker = create("tracker");

            beforeEach(async () => {
                restResetWorkflowTransitions.and.returnValue(Promise.resolve(tracker));
                await resetWorkflowTransitions(context);
            });

            it("begins a new operation", () =>
                expect(context.commit).toHaveBeenCalledWith("beginOperation"));
            it("reset workflow transitions", () =>
                expect(restResetWorkflowTransitions).toHaveBeenCalledWith(1));
            it("update tracker in store", () =>
                expect(context.commit).toHaveBeenCalledWith("saveCurrentTracker", tracker));
            it("ends operation", () => expect(context.commit).toHaveBeenCalledWith("endOperation"));
        });
    });

    describe("updateTransitionRulesEnforcement()", () => {
        let restUpdateTransitionRulesEnforcement;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 }
            };
            restUpdateTransitionRulesEnforcement = jasmine.createSpy(
                "updateTransitionRulesEnforcement"
            );
            rewire$updateTransitionRulesEnforcement(restUpdateTransitionRulesEnforcement);
        });

        describe("when successful", () => {
            const updated_tracker = create("tracker");

            beforeEach(async () => {
                restUpdateTransitionRulesEnforcement.and.returnValue(
                    Promise.resolve(updated_tracker)
                );
                await updateTransitionRulesEnforcement(context, true);
            });

            it("begins a transition rules enforcement", () =>
                expect(context.commit).toHaveBeenCalledWith("beginTransitionRulesEnforcement"));
            it("switches transition rule enforcement", () =>
                expect(restUpdateTransitionRulesEnforcement).toHaveBeenCalledWith(1, true));
            it("update tracker in store", () =>
                expect(context.commit).toHaveBeenCalledWith("saveCurrentTracker", updated_tracker));
            it("ends transition rules enforcement", () =>
                expect(context.commit).toHaveBeenCalledWith("endTransitionRulesEnforcement"));
        });

        describe("when failure", () => {
            let exception = {};

            beforeEach(async () => {
                restUpdateTransitionRulesEnforcement.and.returnValue(Promise.reject(exception));
                getErrorMessage.and.returnValue("error message");
                await updateTransitionRulesEnforcement(context);
            });

            it("extracts message from exceptionoperations", () =>
                expect(getErrorMessage).toHaveBeenCalledWith(exception));
            it("fails operations with extracted message", () =>
                expect(context.commit).toHaveBeenCalledWith("failOperation", "error message"));
        });
    });

    describe("createTransition()", () => {
        let restCreateTransition;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 }
            };
            restCreateTransition = jasmine.createSpy("createTransition");
            rewire$createTransition(restCreateTransition);
        });

        describe("when transition creation is successful", () => {
            const created_transition = { id: 1 };

            beforeEach(async () => {
                restCreateTransition.and.returnValue(Promise.resolve(created_transition));
                await createTransition(context, {
                    from_id: 3,
                    to_id: 9
                });
            });

            it("begins a new operation", () =>
                expect(context.commit).toHaveBeenCalledWith("beginOperation"));
            it("creates transition", () =>
                expect(restCreateTransition).toHaveBeenCalledWith(1, 3, 9));
            it("adds new transition in store", () =>
                expect(context.commit).toHaveBeenCalledWith("addTransition", {
                    id: 1,
                    from_id: 3,
                    to_id: 9
                }));
            it("ends operation", () => expect(context.commit).toHaveBeenCalledWith("endOperation"));
        });
    });

    describe("deleteTransition()", () => {
        let restDeleteTransition;
        let resolveRestCall;
        let rejectRestCall;
        let deleteTransitionPromise;
        const transition = create("transition", { id: 1 });

        beforeEach(() => {
            restDeleteTransition = jasmine.createSpy("deleteTransition");
            rewire$deleteTransition(restDeleteTransition);
            restDeleteTransition.and.returnValue(
                new Promise((resolve, reject) => {
                    resolveRestCall = resolve;
                    rejectRestCall = reject;
                })
            );

            deleteTransitionPromise = deleteTransition(context, transition);
        });

        it("begins a new operation", () =>
            expect(context.commit).toHaveBeenCalledWith("beginOperation"));
        it("creates transition", () => expect(restDeleteTransition).toHaveBeenCalledWith(1));

        describe("when REST call is successful", () => {
            beforeEach(() => {
                resolveRestCall();
                return deleteTransitionPromise;
            });

            it("add new transition in store", () =>
                expect(context.commit).toHaveBeenCalledWith("deleteTransition", transition));
            it("ends operation", () => expect(context.commit).toHaveBeenCalledWith("endOperation"));
        });

        describe("when REST call fail", () => {
            let exception = {};

            beforeEach(() => {
                getErrorMessage.and.returnValue("error message");
                rejectRestCall(exception);
                return deleteTransitionPromise;
            });

            it("does not add new transition in store", () =>
                expect(context.commit).not.toHaveBeenCalledWith(
                    "deleteTransition",
                    jasmine.anything()
                ));
            it("extracts message from exception", () =>
                expect(getErrorMessage).toHaveBeenCalledWith(exception));
            it("fails operation", () =>
                expect(context.commit).toHaveBeenCalledWith("failOperation", "error message"));
        });
    });
});
