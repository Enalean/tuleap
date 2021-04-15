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
import * as tlp from "tlp";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import * as dragDrop from "./drag-drop";
import type { HandleDropContextWithProgramId } from "./drag-drop";
import type { ActionContext } from "vuex";
import type { Feature, State } from "../type";
import type { ProgramIncrement } from "./ProgramIncrement/program-increment-retriever";
import { getProgramIncrementFromId, getToBePlannedElementFromId } from "../store/getters";
import {
    moveFeatureFromBacklogToProgramIncrement,
    moveFeatureFromProgramIncrementToAnotherProgramIncrement,
    moveFeatureFromProgramIncrementToBacklog,
} from "./feature-moving";
import * as backlogAdder from "./ProgramIncrement/add-to-top-backlog";

describe("FeatureMoving", () => {
    let context: ActionContext<State, State>;
    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            state: {
                program_increments: [
                    { id: 101, features: [{ id: 12 }] as Feature[] } as ProgramIncrement,
                    { id: 1, features: [{ id: 12 }] as Feature[] } as ProgramIncrement,
                    { id: 2, features: [] as Feature[] } as ProgramIncrement,
                ],
                to_be_planned_elements: [{ id: 125 } as Feature],
            } as State,
            getters: {},
        } as unknown) as ActionContext<State, State>;
        context.getters = {
            getToBePlannedElementFromId: getToBePlannedElementFromId(context.state),
            getProgramIncrementFromId: getProgramIncrementFromId(context.state),
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
                101
            );

            expect(context.commit).not.toHaveBeenCalled();
        });

        it(`When feature is moving from backlog to Program Increment, Then feature is added to program increment`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "125");

            const put = jest.spyOn(tlp, "put");
            mockFetchSuccess(put);
            const plan_feature = jest.spyOn(dragDrop, "planFeatureInProgramIncrement");

            await moveFeatureFromBacklogToProgramIncrement(
                context,
                {
                    dropped_element,
                } as HandleDropContextWithProgramId,
                101
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromBacklogToProgramIncrement",
                {
                    feature_id: 125,
                    program_increment_id: 101,
                }
            );

            expect(plan_feature).toHaveBeenCalledWith(
                {
                    dropped_element,
                },
                101,
                125
            );
        });

        it(`When a error is thrown during plan elements, Then error is stored`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "125");
            const target_dropzone = createElement();
            target_dropzone.setAttribute("data-artifact-link-field-id", "1234");

            const put = jest.spyOn(tlp, "put");
            mockFetchError(put, {
                status: 404,
                error_json: { error: { code: 404, message: "Error" } },
            });

            const plan_feature = jest.spyOn(dragDrop, "planFeatureInProgramIncrement");

            await moveFeatureFromBacklogToProgramIncrement(
                context,
                {
                    dropped_element,
                    target_dropzone,
                } as HandleDropContextWithProgramId,
                101
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromBacklogToProgramIncrement",
                {
                    feature_id: 125,
                    program_increment_id: 101,
                }
            );

            expect(plan_feature).toHaveBeenCalledWith(
                {
                    dropped_element,
                    target_dropzone,
                },
                101,
                125
            );

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
                101
            );

            expect(context.commit).not.toHaveBeenCalled();
        });

        it(`When feature is moving from Program Increment to backlog, Then feature is added to backlog`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");

            const move_element_from_program_increment_to_top_backlog = jest.spyOn(
                backlogAdder,
                "moveElementFromProgramIncrementToTopBackLog"
            );
            const patch = jest.spyOn(tlp, "patch");
            mockFetchSuccess(patch);

            await moveFeatureFromProgramIncrementToBacklog(
                context,
                {
                    dropped_element,
                    program_id: 101,
                } as HandleDropContextWithProgramId,
                101
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromProgramIncrementToBacklog",
                {
                    feature_id: 12,
                    program_increment_id: 101,
                }
            );

            expect(move_element_from_program_increment_to_top_backlog).toHaveBeenCalledWith(
                101,
                12
            );
        });

        it(`When an error is thrown during remove elements from program increment, Then error is stored`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");

            const move_element_from_program_increment_to_top_backlog = jest.spyOn(
                backlogAdder,
                "moveElementFromProgramIncrementToTopBackLog"
            );
            const patch = jest.spyOn(tlp, "patch");
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
                101
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromProgramIncrementToBacklog",
                {
                    feature_id: 12,
                    program_increment_id: 101,
                }
            );

            expect(move_element_from_program_increment_to_top_backlog).toHaveBeenCalledWith(
                101,
                12
            );

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
                102
            );

            expect(context.commit).not.toHaveBeenCalled();
        });

        it(`When feature is moving from increment to another increment, Then feature is unplanned and planned`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            const target_dropzone = createElement();

            const plan_feature = jest.spyOn(dragDrop, "planFeatureInProgramIncrement");
            const unplan_feature = jest.spyOn(dragDrop, "unplanFeature");
            const put = jest.spyOn(tlp, "put");
            mockFetchSuccess(put);

            const handle_drop = {
                dropped_element,
                target_dropzone,
            } as HandleDropContextWithProgramId;

            await moveFeatureFromProgramIncrementToAnotherProgramIncrement(
                context,
                handle_drop,
                2,
                1
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromProgramIncrementToAnotherProgramIncrement",
                {
                    feature_id: 12,
                    from_program_increment_id: 1,
                    to_program_increment_id: 2,
                }
            );

            expect(unplan_feature).toHaveBeenCalledWith(handle_drop, 1, 12);
            expect(plan_feature).toHaveBeenCalledWith(handle_drop, 2, 12);
        });

        it(`When feature are moving in the same program increment, Then nothing happen`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const plan_feature = jest.spyOn(dragDrop, "planFeatureInProgramIncrement");
            const unplan_feature = jest.spyOn(dragDrop, "unplanFeature");

            await moveFeatureFromProgramIncrementToAnotherProgramIncrement(
                context,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                    program_id: 101,
                } as HandleDropContextWithProgramId,
                1,
                1
            );

            expect(unplan_feature).not.toHaveBeenCalled();
            expect(plan_feature).not.toHaveBeenCalled();
        });

        it(`When an error is thrown moving in the same program increment, Then error is stored`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            dropped_element.setAttribute("data-artifact-link-field-id", "1234");
            const target_dropzone = createElement();

            const plan_feature = jest.spyOn(dragDrop, "planFeatureInProgramIncrement");
            const unplan_feature = jest.spyOn(dragDrop, "unplanFeature");

            const put = jest.spyOn(tlp, "put");
            mockFetchError(put, {
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
                1
            );

            expect(context.commit).toHaveBeenCalledWith(
                "moveFeatureFromProgramIncrementToAnotherProgramIncrement",
                {
                    feature_id: 12,
                    from_program_increment_id: 1,
                    to_program_increment_id: 2,
                }
            );

            expect(unplan_feature).toHaveBeenCalledWith(handle_drop, 1, 12);
            expect(plan_feature).toHaveBeenCalledWith(handle_drop, 2, 12);

            expect(context.commit).toHaveBeenCalledWith("setModalErrorMessage", "404 Error");
        });
    });
});
