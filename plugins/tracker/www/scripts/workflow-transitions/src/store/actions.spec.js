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
    saveNewTransition,
    loadTracker,
    resetWorkflowTransitionsField,
    saveWorkflowTransitionsField,
    switchTransitionRulesEnforcement
} from "./actions.js";
import {
    restore as restore$RestQuerier,
    rewire$createTransition,
    rewire$createWorkflowTransitions,
    rewire$getTracker,
    rewire$resetWorkflowTransitions,
    rewire$updateTransitionRulesEnforcement
} from "../api/rest-querier.js";
import { restore as restore$ExceptionHandler, rewire$getErrorMessage } from "./exceptionHandler.js";

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
            const tracker = { id: 12 };
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

    describe("saveWorkflowTransitionsField()", () => {
        let createWorkflowTransitions;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 }
            };
            createWorkflowTransitions = jasmine.createSpy("createWorkflowTransitions");
            rewire$createWorkflowTransitions(createWorkflowTransitions);
        });

        describe("when workflow creation is successful", () => {
            beforeEach(async () => {
                mockFetchSuccess(createWorkflowTransitions);
                await saveWorkflowTransitionsField(context, 9);
            });

            it("begins a new operation", () =>
                expect(context.commit).toHaveBeenCalledWith("beginOperation"));
            it("creates workflow", () =>
                expect(createWorkflowTransitions).toHaveBeenCalledWith(1, 9));
            it("creates workflow in store", () =>
                expect(context.commit).toHaveBeenCalledWith("createWorkflow", 9));
            it("ends operation", () => expect(context.commit).toHaveBeenCalledWith("endOperation"));
        });
    });

    describe("resetWorkflowTransitionsField()", () => {
        let resetWorkflowTransitions;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 }
            };
            resetWorkflowTransitions = jasmine.createSpy("resetWorkflowTransitions");
            rewire$resetWorkflowTransitions(resetWorkflowTransitions);
        });

        describe("when reset workflow transitions is successful", () => {
            const tracker = { id: 12 };

            beforeEach(async () => {
                resetWorkflowTransitions.and.returnValue(Promise.resolve(tracker));
                await resetWorkflowTransitionsField(context);
            });

            it("begins a new operation", () =>
                expect(context.commit).toHaveBeenCalledWith("beginOperation"));
            it("reset workflow transitions", () =>
                expect(resetWorkflowTransitions).toHaveBeenCalledWith(1));
            it("update tracker in store", () =>
                expect(context.commit).toHaveBeenCalledWith("saveCurrentTracker", tracker));
            it("ends operation", () => expect(context.commit).toHaveBeenCalledWith("endOperation"));
        });
    });

    describe("switchTransitionRulesEnforcement()", () => {
        let updateTransitionRulesEnforcement;

        beforeEach(() => {
            context = {
                ...context,
                getters: { current_tracker_id: 1 }
            };
            updateTransitionRulesEnforcement = jasmine.createSpy(
                "updateTransitionRulesEnforcement"
            );
            rewire$updateTransitionRulesEnforcement(updateTransitionRulesEnforcement);
        });

        describe("when successful", () => {
            const updated_tracker = { id: 12 };

            beforeEach(async () => {
                updateTransitionRulesEnforcement.and.returnValue(Promise.resolve(updated_tracker));
                await switchTransitionRulesEnforcement(context, true);
            });

            it("begins a transition rules enforcement", () =>
                expect(context.commit).toHaveBeenCalledWith("beginTransitionRulesEnforcement"));
            it("switches transition rule enforcement", () =>
                expect(updateTransitionRulesEnforcement).toHaveBeenCalledWith(1, true));
            it("update tracker in store", () =>
                expect(context.commit).toHaveBeenCalledWith("saveCurrentTracker", updated_tracker));
            it("ends transition rules enforcement", () =>
                expect(context.commit).toHaveBeenCalledWith("endTransitionRulesEnforcement"));
        });

        describe("when failure", () => {
            let exception = {};

            beforeEach(async () => {
                updateTransitionRulesEnforcement.and.returnValue(Promise.reject(exception));
                getErrorMessage.and.returnValue("error message");
                await switchTransitionRulesEnforcement(context);
            });

            it("extracts message from exceptionoperations", () =>
                expect(getErrorMessage).toHaveBeenCalledWith(exception));
            it("fails operations with extracted message", () =>
                expect(context.commit).toHaveBeenCalledWith("failOperation", "error message"));
        });
    });

    describe("saveNewTransition()", () => {
        let createTransition;

        beforeEach(() => {
            context = {
                ...context,
                state: { current_tracker: { workflow: { transitions: [{ id: 1 }] } } },
                getters: { current_tracker_id: 1 }
            };
            createTransition = jasmine.createSpy("createTransition");
            rewire$createTransition(createTransition);
        });

        describe("when transition creation is successful", () => {
            beforeEach(async () => {
                createTransition.and.returnValue(Promise.resolve({ id: 2 }));
                await saveNewTransition(context, {
                    from_id: 3,
                    to_id: 9
                });
            });

            it("begins a new operation", () =>
                expect(context.commit).toHaveBeenCalledWith("beginOperation"));
            it("creates transition", () => expect(createTransition).toHaveBeenCalledWith(1, 3, 9));
            it("add new transition in store", () =>
                expect(context.commit).toHaveBeenCalledWith("addTransition", {
                    id: 2,
                    from_id: 3,
                    to_id: 9
                }));
            it("ends operation", () => expect(context.commit).toHaveBeenCalledWith("endOperation"));
        });
    });
});
