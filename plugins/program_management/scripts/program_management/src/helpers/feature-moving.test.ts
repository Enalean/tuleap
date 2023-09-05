/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import { createElement } from "./jest/create-dom-element";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import type { HandleDropContextWithProgramId } from "./drag-drop";
import type { ActionContext } from "vuex";
import type { Feature, State } from "../type";
import type { ProgramIncrement } from "./ProgramIncrement/program-increment-retriever";
import {
    getFeatureInProgramIncrement,
    getFeaturesInProgramIncrement,
    getProgramIncrementFromId,
    getSiblingFeatureFromProgramBacklog,
    getToBePlannedElementFromId,
} from "../store/getters";
import {
    moveFeatureFromBacklogToProgramIncrement,
    moveFeatureFromProgramIncrementToAnotherProgramIncrement,
    moveFeatureFromProgramIncrementToBacklog,
} from "./feature-moving";
import * as backlogAdder from "./ProgramIncrement/add-to-top-backlog";
import * as featurePlanner from "./ProgramIncrement/Feature/feature-planner";

describe("FeatureMoving", () => {
    let context: ActionContext<State, State>;
    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: {
                program_increments: [
                    { id: 101, features: [{ id: 12 }] as Feature[] } as ProgramIncrement,
                    { id: 1, features: [{ id: 12 }] as Feature[] } as ProgramIncrement,
                    { id: 2, features: [] as Feature[] } as ProgramIncrement,
                ],
                to_be_planned_elements: [{ id: 125 } as Feature, { id: 99 } as Feature],
            } as State,
            getters: {},
        } as unknown as ActionContext<State, State>;
        context.getters = {
            getToBePlannedElementFromId: getToBePlannedElementFromId(context.state),
            getProgramIncrementFromId: getProgramIncrementFromId(context.state),
            getFeatureInProgramIncrement: getFeatureInProgramIncrement(context.state),
            getFeaturesInProgramIncrement: getFeaturesInProgramIncrement(context.state),
            getSiblingFeatureFromProgramBacklog: getSiblingFeatureFromProgramBacklog(context.state),
        };
    });
    describe("moveFeatureFromBacklogToProgramIncrement", () => {
        it("When no data element-id found, Then nothing happens", () => {
            const dropped_element = createElement();
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            moveFeatureFromBacklogToProgramIncrement(
                context,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                } as HandleDropContextWithProgramId,
                101,
            );

            expect(context.commit).not.toHaveBeenCalled();
        });

        it(`When feature is moving from backlog to Program Increment, Then feature is added to program increment`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "125");

            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(patch);
            const plan_feature = jest.spyOn(featurePlanner, "planElementInProgramIncrement");

            await moveFeatureFromBacklogToProgramIncrement(
                context,
                {
                    dropped_element,
                } as HandleDropContextWithProgramId,
                101,
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromBacklogToProgramIncrement",
                {
                    feature: { id: 125 },
                    order: { compared_to: 12, direction: "after" },
                    to_program_increment_id: 101,
                },
            );

            expect(plan_feature).toHaveBeenCalledWith({
                feature: { id: 125 },
                order: { compared_to: 12, direction: "after" },
                to_program_increment_id: 101,
            });
        });

        it(`When a error is thrown during plan elements, Then error is stored`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "125");
            const target_dropzone = createElement();

            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchError(patch, {
                status: 404,
                error_json: { error: { code: 404, message: "Error" } },
            });

            const plan_feature = jest.spyOn(featurePlanner, "planElementInProgramIncrement");

            await moveFeatureFromBacklogToProgramIncrement(
                context,
                {
                    dropped_element,
                    target_dropzone,
                } as HandleDropContextWithProgramId,
                101,
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromBacklogToProgramIncrement",
                {
                    feature: { id: 125 },
                    order: { compared_to: 12, direction: "after" },
                    to_program_increment_id: 101,
                },
            );

            expect(plan_feature).toHaveBeenCalledWith({
                feature: { id: 125 },
                order: { compared_to: 12, direction: "after" },
                to_program_increment_id: 101,
            });

            expect(context.commit).toHaveBeenCalledWith("setModalErrorMessage", "404 Error");
        });
    });

    describe("moveFeatureFromProgramIncrementToBacklog", () => {
        it("When no data element-id found, Then nothing happens", () => {
            const dropped_element = createElement();
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            moveFeatureFromProgramIncrementToBacklog(
                context,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                } as HandleDropContextWithProgramId,
                101,
            );

            expect(context.commit).not.toHaveBeenCalled();
        });

        it(`When feature is moving from Program Increment to backlog, Then feature is added to backlog`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            const next_sibling: Element = createElement();
            next_sibling.setAttribute("data-element-id", "99");

            const move_element_from_program_increment_to_top_backlog = jest.spyOn(
                backlogAdder,
                "moveElementFromProgramIncrementToTopBackLog",
            );
            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(patch);

            await moveFeatureFromProgramIncrementToBacklog(
                context,
                {
                    dropped_element,
                    next_sibling,
                    program_id: 101,
                } as HandleDropContextWithProgramId,
                101,
            );

            expect(context.commit).toHaveBeenCalledWith("removeFeatureFromProgramIncrement", {
                feature_id: 12,
                program_increment_id: 101,
            });
            expect(context.commit).toHaveBeenCalledWith("addToBePlannedElement", {
                feature: { id: 12 },
                order: { compared_to: 125, direction: "after" },
            });

            expect(move_element_from_program_increment_to_top_backlog).toHaveBeenCalledWith(101, {
                feature: { id: 12 },
                order: { compared_to: 125, direction: "after" },
            });
        });

        it(`When sibling feature does not exist and no element in backlog, Then FeatureReorderPosition is null`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");

            const move_element_from_program_increment_to_top_backlog = jest.spyOn(
                backlogAdder,
                "moveElementFromProgramIncrementToTopBackLog",
            );
            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(patch);

            context.state.to_be_planned_elements = [];
            await moveFeatureFromProgramIncrementToBacklog(
                context,
                {
                    dropped_element,
                    next_sibling: null,
                    program_id: 101,
                } as HandleDropContextWithProgramId,
                101,
            );

            expect(context.commit).toHaveBeenCalledWith("removeFeatureFromProgramIncrement", {
                feature_id: 12,
                program_increment_id: 101,
            });
            expect(context.commit).toHaveBeenCalledWith("addToBePlannedElement", {
                feature: { id: 12 },
                order: null,
            });

            expect(move_element_from_program_increment_to_top_backlog).toHaveBeenCalledWith(101, {
                feature: { id: 12 },
                order: null,
            });
        });

        it(`When an error is thrown during remove elements from program increment, Then error is stored`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");

            const move_element_from_program_increment_to_top_backlog = jest.spyOn(
                backlogAdder,
                "moveElementFromProgramIncrementToTopBackLog",
            );
            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchError(patch, {
                status: 404,
                error_json: { error: { code: 404, message: "Error" } },
            });

            await moveFeatureFromProgramIncrementToBacklog(
                context,
                {
                    dropped_element,
                    program_id: 101,
                } as HandleDropContextWithProgramId,
                101,
            );

            expect(context.commit).toHaveBeenCalledWith("removeFeatureFromProgramIncrement", {
                feature_id: 12,
                program_increment_id: 101,
            });
            expect(context.commit).toHaveBeenCalledWith("addToBePlannedElement", {
                feature: { id: 12 },
                order: { compared_to: 99, direction: "after" },
            });

            expect(move_element_from_program_increment_to_top_backlog).toHaveBeenCalledWith(101, {
                feature: { id: 12 },
                order: { compared_to: 99, direction: "after" },
            });

            expect(context.commit).toHaveBeenCalledWith("setModalErrorMessage", "404 Error");
        });
    });

    describe("moveFeatureFromProgramIncrementToAnotherProgramIncrement", () => {
        it("When no data element-id found, Then nothing happens", () => {
            const dropped_element = createElement();

            moveFeatureFromProgramIncrementToAnotherProgramIncrement(
                context,
                {
                    dropped_element,
                } as HandleDropContextWithProgramId,
                101,
                102,
            );

            expect(context.commit).not.toHaveBeenCalled();
        });

        it(`When feature is moving from increment to another increment, Then feature is planned in the other program increment`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            const target_dropzone = createElement();

            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(patch);
            const plan_feature = jest.spyOn(featurePlanner, "planElementInProgramIncrement");

            const handle_drop = {
                dropped_element,
                target_dropzone,
            } as HandleDropContextWithProgramId;

            await moveFeatureFromProgramIncrementToAnotherProgramIncrement(
                context,
                handle_drop,
                2,
                1,
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromProgramIncrementToAnotherProgramIncrement",
                {
                    feature: { id: 12 },
                    order: null,
                    from_program_increment_id: 1,
                    to_program_increment_id: 2,
                },
            );

            expect(plan_feature).toHaveBeenCalledWith({
                feature: { id: 12 },
                order: null,
                from_program_increment_id: 1,
                to_program_increment_id: 2,
            });
        });

        it(`When feature are moving in the same program increment, Then nothing happen`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(patch);
            const plan_feature = jest.spyOn(featurePlanner, "planElementInProgramIncrement");

            await moveFeatureFromProgramIncrementToAnotherProgramIncrement(
                context,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                    program_id: 101,
                } as HandleDropContextWithProgramId,
                1,
                1,
            );

            expect(plan_feature).not.toHaveBeenCalled();
        });

        it(`When an error is thrown moving in the same program increment, Then error is stored`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            const target_dropzone = createElement();

            const plan_feature = jest.spyOn(featurePlanner, "planElementInProgramIncrement");

            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchError(patch, {
                status: 404,
                error_json: { error: { code: 404, message: "Error" } },
            });

            const handle_drop = {
                dropped_element,
                target_dropzone,
            } as HandleDropContextWithProgramId;

            await moveFeatureFromProgramIncrementToAnotherProgramIncrement(
                context,
                handle_drop,
                2,
                1,
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromProgramIncrementToAnotherProgramIncrement",
                {
                    feature: { id: 12 },
                    order: null,
                    from_program_increment_id: 1,
                    to_program_increment_id: 2,
                },
            );

            expect(plan_feature).toHaveBeenCalledWith({
                feature: { id: 12 },
                order: null,
                from_program_increment_id: 1,
                to_program_increment_id: 2,
            });

            expect(context.commit).toHaveBeenCalledWith("setModalErrorMessage", "404 Error");
        });
    });
});
