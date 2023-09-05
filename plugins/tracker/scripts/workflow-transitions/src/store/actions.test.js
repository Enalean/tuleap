/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import {
    createTransition,
    loadTracker,
    resetWorkflowTransitions,
    createWorkflowTransitions,
    updateTransitionRulesEnforcement,
    deleteTransition,
    deactivateLegacyTransitions,
    changeWorkflowMode,
} from "./actions.js";
import * as rest_querier from "../api/rest-querier.js";
import * as exception_handler from "./exception-handler.js";
import { create } from "../support/factories.js";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("Store actions:", () => {
    let context;
    let getErrorMessage;

    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: {},
        };
        getErrorMessage = jest.spyOn(exception_handler, "getErrorMessage");
    });

    describe("loadTracker()", () => {
        let getTracker;
        beforeEach(() => {
            getTracker = jest.spyOn(rest_querier, "getTracker");
        });

        it("fetches tracker asynchronously and store it as current tracker", async () => {
            const tracker = create("tracker");
            getTracker.mockReturnValue(Promise.resolve(tracker));

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
                getters: { current_tracker_id: 1 },
            };
            restCreateWorkflowTransitions = jest.spyOn(rest_querier, "createWorkflowTransitions");
        });

        describe("when workflow creation is successful", () => {
            const tracker = create("tracker");

            beforeEach(async () => {
                restCreateWorkflowTransitions.mockReturnValue(Promise.resolve(tracker));
                await createWorkflowTransitions(context, 9);
            });

            it("begins a new operation", () =>
                expect(context.commit).toHaveBeenCalledWith("beginOperation"));
            it("creates workflow", () =>
                expect(restCreateWorkflowTransitions).toHaveBeenCalledWith(1, 9));
            it("creates workflow in store", () =>
                expect(context.commit).toHaveBeenCalledWith("createWorkflow", tracker));
            it("ends operation", () => expect(context.commit).toHaveBeenCalledWith("endOperation"));
        });
    });

    describe("resetWorkflowTransitions()", () => {
        let restResetWorkflowTransitions;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 },
            };
            restResetWorkflowTransitions = jest.spyOn(rest_querier, "resetWorkflowTransitions");
        });

        describe("when reset workflow transitions is successful", () => {
            const tracker = create("tracker");

            beforeEach(async () => {
                restResetWorkflowTransitions.mockReturnValue(Promise.resolve(tracker));
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
                getters: { current_tracker_id: 1 },
            };
            restUpdateTransitionRulesEnforcement = jest.spyOn(
                rest_querier,
                "updateTransitionRulesEnforcement",
            );
        });

        describe("when successful", () => {
            const updated_tracker = create("tracker");

            beforeEach(async () => {
                restUpdateTransitionRulesEnforcement.mockReturnValue(
                    Promise.resolve(updated_tracker),
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
                restUpdateTransitionRulesEnforcement.mockReturnValue(Promise.reject(exception));
                getErrorMessage.mockReturnValue("error message");
                await updateTransitionRulesEnforcement(context);
            });

            it("extracts message from exception operations", () =>
                expect(getErrorMessage).toHaveBeenCalledWith(exception));
            it("fails operations with extracted message", () =>
                expect(context.commit).toHaveBeenCalledWith("failOperation", "error message"));
        });
    });

    describe("changeWorkflowMode()", () => {
        let restChangeWorkflowMode;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 7 },
            };
            restChangeWorkflowMode = jest.spyOn(rest_querier, "changeWorkflowMode");
        });

        it("when successful, it changes the workflow mode and updates the tracker", async () => {
            const updated_tracker = create("tracker");

            restChangeWorkflowMode.mockReturnValue(Promise.resolve(updated_tracker));
            const is_workflow_advanced = true;
            await changeWorkflowMode(context, is_workflow_advanced);

            expect(context.commit).toHaveBeenCalledWith("beginWorkflowModeChange");
            expect(restChangeWorkflowMode).toHaveBeenCalledWith(7, is_workflow_advanced);
            expect(context.commit).toHaveBeenCalledWith("saveCurrentTracker", updated_tracker);
            expect(context.commit).toHaveBeenCalledWith("endWorkflowModeChange");
        });

        it("when there is an error, it marks the operation as failed with the error message", async () => {
            const error = {};
            restChangeWorkflowMode.mockReturnValue(Promise.reject(error));
            getErrorMessage.mockReturnValue("error message");
            await changeWorkflowMode(context, true);

            expect(context.commit).toHaveBeenCalledWith("failOperation", "error message");
        });
    });

    describe("createTransition()", () => {
        let restCreateTransition;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 },
            };
            restCreateTransition = jest.spyOn(rest_querier, "createTransition");
        });

        describe("when transition creation is successful", () => {
            const created_transition = { id: 1 };

            beforeEach(async () => {
                restCreateTransition.mockReturnValue(Promise.resolve(created_transition));
                await createTransition(context, {
                    from_id: 3,
                    to_id: 9,
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
                    to_id: 9,
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
            restDeleteTransition = jest.spyOn(rest_querier, "deleteTransition");
            restDeleteTransition.mockReturnValue(
                new Promise((resolve, reject) => {
                    resolveRestCall = resolve;
                    rejectRestCall = reject;
                }),
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
                getErrorMessage.mockReturnValue("error message");
                rejectRestCall(exception);
                return deleteTransitionPromise;
            });

            it("does not add new transition in store", () =>
                expect(context.commit).not.toHaveBeenCalledWith(
                    "deleteTransition",
                    expect.anything(),
                ));
            it("extracts message from exception", () =>
                expect(getErrorMessage).toHaveBeenCalledWith(exception));
            it("fails operation", () =>
                expect(context.commit).toHaveBeenCalledWith("failOperation", "error message"));
        });
    });

    describe("deactivateLegacyTransitions()", () => {
        let restDeactivateLegacyTransitions;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 },
            };
            restDeactivateLegacyTransitions = jest.spyOn(
                rest_querier,
                "deactivateLegacyTransitions",
            );
        });

        describe("when deactivate legacy transitions is successful", () => {
            const tracker = create("tracker");

            beforeEach(async () => {
                restDeactivateLegacyTransitions.mockReturnValue(Promise.resolve(tracker));
                await deactivateLegacyTransitions(context);
            });

            it("begins a new operation", () =>
                expect(context.commit).toHaveBeenCalledWith("beginOperation"));
            it("reset workflow transitions", () =>
                expect(restDeactivateLegacyTransitions).toHaveBeenCalledWith(1));
            it("update tracker in store", () =>
                expect(context.commit).toHaveBeenCalledWith("saveCurrentTracker", tracker));
            it("ends operation", () => expect(context.commit).toHaveBeenCalledWith("endOperation"));
        });
    });
});
