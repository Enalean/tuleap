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

import { mockFetchSuccess, mockFetchError } from "tlp-mocks";
import {
    loadTracker,
    saveWorkflowTransitionsField,
    resetWorkflowTransitionsField
} from "./actions.js";
import {
    rewire$getTracker,
    rewire$createWorkflowTransitions,
    rewire$resetWorkflowTransitions
} from "../api/rest-querier.js";

describe("Store actions:", () => {
    let context;
    beforeEach(() => {
        context = {
            commit: jasmine.createSpy("commit"),
            state: {}
        };
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
                state: {
                    current_tracker: { id: 1 }
                }
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
            context = { ...context, state: { current_tracker: { id: 1 } } };
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
});
