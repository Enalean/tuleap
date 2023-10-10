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

import {
    getFeaturePlanningChange,
    getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement,
    getFeaturePlanningChangeInProgramIncrement,
    reorderFeatureInProgramBacklog,
    reorderFeatureInSameProgramIncrement,
} from "./feature-reordering";
import type { Feature, State } from "../type";
import type { ActionContext } from "vuex";
import type { HandleDropContextWithProgramId } from "./drag-drop";
import { createElement } from "./jest/create-dom-element";
import * as backlogAdder from "./ProgramIncrement/add-to-top-backlog";
import * as featurePlanner from "./ProgramIncrement/Feature/feature-planner";
import * as tlp_fetch from "@tuleap/tlp-fetch";
import { mockFetchError, mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import {
    getFeatureInProgramIncrement,
    getFeaturesInProgramIncrement,
    getSiblingFeatureFromProgramBacklog,
    getSiblingFeatureInProgramIncrement,
    getToBePlannedElementFromId,
} from "../store/getters";
import type { ProgramIncrement } from "./ProgramIncrement/program-increment-retriever";

describe("Feature Reordering", () => {
    describe("getFeatureReorderPosition", () => {
        it("When sibling is null, Then we get a position after the last feature of the list", () => {
            const feature: Feature = { id: 115 } as Feature;
            const backlog = [feature, { id: 116 }, { id: 117 }] as Feature[];
            const position = getFeaturePlanningChange(feature, null, backlog);

            expect(position).toEqual({
                feature: { id: 115 },
                order: {
                    direction: "after",
                    compared_to: 117,
                },
            });
        });

        it("When feature is moving between 2 features, Then we get a position after the first feature", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 117 } as Feature;
            const backlog = [feature, { id: 116 }, sibling] as Feature[];
            const position = getFeaturePlanningChange(feature, sibling, backlog);

            expect(position).toEqual({
                feature: { id: 115 },
                order: {
                    direction: "after",
                    compared_to: 116,
                },
            });
        });

        it("When feature is moving at the first place, Then we get a position before the first feature", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 116 } as Feature;
            const backlog = [sibling, { id: 111 }, feature] as Feature[];
            const position = getFeaturePlanningChange(feature, sibling, backlog);

            expect(position).toEqual({
                feature: { id: 115 },
                order: {
                    direction: "before",
                    compared_to: 116,
                },
            });
        });

        it("When sibling does not exist in the backlog, Then error is thrown", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 666 } as Feature;
            const backlog = [feature] as Feature[];
            expect(() => getFeaturePlanningChange(feature, sibling, backlog)).toThrow(
                "Cannot find feature with id #666",
            );
        });

        it("When backlog is empty, Then FeatureReorder is null", () => {
            const feature: Feature = { id: 115 } as Feature;
            expect(getFeaturePlanningChange(feature, null, [])).toEqual({
                feature: { id: 115 },
                order: null,
            });
        });
    });

    describe("reorderFeatureInProgramBacklog", () => {
        let context: ActionContext<State, State>;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
                state: {
                    to_be_planned_elements: [{ id: 56 }, { id: 57 }, { id: 58 }] as Feature[],
                } as State,
                getters: {},
            } as unknown as ActionContext<State, State>;
            context.getters = {
                getToBePlannedElementFromId: getToBePlannedElementFromId(context.state),
                getSiblingFeatureFromProgramBacklog: getSiblingFeatureFromProgramBacklog(
                    context.state,
                ),
            };
        });

        it("When no data element-id found, Then nothing happens", () => {
            const dropped_element = createElement();
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            reorderFeatureInProgramBacklog(context, {
                dropped_element,
                source_dropzone,
                target_dropzone,
            } as HandleDropContextWithProgramId);

            expect(context.commit).not.toHaveBeenCalled();
        });

        it(`When sibling is null, Then element is moving to the bottom`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "56");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const reorder_element_in_backlog = jest.spyOn(
                backlogAdder,
                "reorderElementInTopBacklog",
            );
            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(patch);

            await reorderFeatureInProgramBacklog(context, {
                dropped_element,
                source_dropzone,
                target_dropzone,
                program_id: 101,
                next_sibling: null,
            } as HandleDropContextWithProgramId);

            const reorder_payload = {
                feature: { id: 56 },
                order: {
                    direction: "after",
                    compared_to: 58,
                },
            };

            expect(context.commit).toHaveBeenCalledWith(
                "changeFeaturePositionInProgramBacklog",
                reorder_payload,
            );

            expect(reorder_element_in_backlog).toHaveBeenCalledWith(101, reorder_payload);
        });

        it(`When sibling is not null, Then element is moving to before the sibling`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "57");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const sibling_element = createElement();
            sibling_element.setAttribute("data-element-id", "56");

            const reorder_element_in_backlog = jest.spyOn(
                backlogAdder,
                "reorderElementInTopBacklog",
            );
            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(patch);

            await reorderFeatureInProgramBacklog(context, {
                dropped_element,
                source_dropzone,
                target_dropzone,
                program_id: 101,
                next_sibling: sibling_element,
            } as HandleDropContextWithProgramId);

            const reorder_payload = {
                feature: { id: 57 },
                order: {
                    direction: "before",
                    compared_to: 56,
                },
            };

            expect(context.commit).toHaveBeenCalledWith(
                "changeFeaturePositionInProgramBacklog",
                reorder_payload,
            );

            expect(reorder_element_in_backlog).toHaveBeenCalledWith(101, reorder_payload);
        });

        it(`When sibling has not element-id data attribute, Then element is moving to the bottom`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "57");
            const source_dropzone = createElement();
            const target_dropzone = createElement();
            const sibling_element = createElement();

            const reorder_element_in_backlog = jest.spyOn(
                backlogAdder,
                "reorderElementInTopBacklog",
            );

            await reorderFeatureInProgramBacklog(context, {
                dropped_element,
                source_dropzone,
                target_dropzone,
                program_id: 101,
                next_sibling: sibling_element,
            } as HandleDropContextWithProgramId);

            const reorder_payload = {
                feature: { id: 57 },
                order: {
                    direction: "after",
                    compared_to: 58,
                },
            };

            expect(context.commit).toHaveBeenCalledWith(
                "changeFeaturePositionInProgramBacklog",
                reorder_payload,
            );

            expect(reorder_element_in_backlog).toHaveBeenCalledWith(101, reorder_payload);
        });

        it(`When error is thrown reordering elements, Then error is stored`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "57");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const sibling_element = createElement();
            sibling_element.setAttribute("data-element-id", "56");

            const reorder_element_in_backlog = jest.spyOn(
                backlogAdder,
                "reorderElementInTopBacklog",
            );
            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchError(patch, {
                status: 404,
                error_json: { error: { code: 404, message: "Error" } },
            });

            await reorderFeatureInProgramBacklog(context, {
                dropped_element,
                source_dropzone,
                target_dropzone,
                program_id: 101,
                next_sibling: sibling_element,
            } as HandleDropContextWithProgramId);

            const reorder_payload = {
                feature: { id: 57 },
                order: {
                    direction: "before",
                    compared_to: 56,
                },
            };

            expect(context.commit).toHaveBeenCalledWith(
                "changeFeaturePositionInProgramBacklog",
                reorder_payload,
            );

            expect(reorder_element_in_backlog).toHaveBeenCalledWith(101, reorder_payload);
            expect(context.commit).toHaveBeenCalledWith("setModalErrorMessage", "404 Error");
        });
    });

    describe("getFeaturePlanningChangeInProgramIncrement", () => {
        it("When sibling is null, Then we get a position after the last feature of the list", () => {
            const feature: Feature = { id: 115 } as Feature;
            const features = [feature, { id: 116 }, { id: 117 }] as Feature[];
            const position = getFeaturePlanningChangeInProgramIncrement(feature, null, features, 4);

            expect(position).toEqual({
                feature: { id: 115 },
                order: {
                    direction: "after",
                    compared_to: 117,
                },
                to_program_increment_id: 4,
            });
        });

        it("When feature is moving between 2 features, Then we get a position after the first feature", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 117 } as Feature;
            const features = [feature, { id: 116 }, sibling] as Feature[];
            const position = getFeaturePlanningChangeInProgramIncrement(
                feature,
                sibling,
                features,
                4,
            );

            expect(position).toEqual({
                feature: { id: 115 },
                order: {
                    direction: "after",
                    compared_to: 116,
                },
                to_program_increment_id: 4,
            });
        });

        it("When feature is moving at the first place, Then we get a position before the first feature", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 116 } as Feature;
            const features = [sibling, { id: 111 }, feature] as Feature[];
            const position = getFeaturePlanningChangeInProgramIncrement(
                feature,
                sibling,
                features,
                4,
            );

            expect(position).toEqual({
                feature: { id: 115 },
                order: {
                    direction: "before",
                    compared_to: 116,
                },
                to_program_increment_id: 4,
            });
        });

        it("When sibling does not exist in the program increment, Then error is thrown", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 666 } as Feature;
            const features = [feature] as Feature[];
            expect(() =>
                getFeaturePlanningChangeInProgramIncrement(feature, sibling, features, 4),
            ).toThrow("Cannot find feature with id #666");
        });

        it("When there are no features in program increment, Then FeatureReorder is null", () => {
            const feature: Feature = { id: 115 } as Feature;
            expect(getFeaturePlanningChangeInProgramIncrement(feature, null, [], 4)).toEqual({
                feature: { id: 115 },
                order: null,
                to_program_increment_id: 4,
            });
        });
    });

    describe("getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement", () => {
        it("When sibling is null, Then we get a position after the last feature of the list", () => {
            const feature: Feature = { id: 115 } as Feature;
            const features = [feature, { id: 116 }, { id: 117 }] as Feature[];
            const position = getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement(
                feature,
                null,
                features,
                4,
                18,
            );

            expect(position).toEqual({
                feature: { id: 115 },
                order: {
                    direction: "after",
                    compared_to: 117,
                },
                from_program_increment_id: 4,
                to_program_increment_id: 18,
            });
        });

        it("When feature is moving between 2 features, Then we get a position after the first feature", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 117 } as Feature;
            const features = [feature, { id: 116 }, sibling] as Feature[];
            const position = getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement(
                feature,
                sibling,
                features,
                4,
                18,
            );

            expect(position).toEqual({
                feature: { id: 115 },
                order: {
                    direction: "after",
                    compared_to: 116,
                },
                from_program_increment_id: 4,
                to_program_increment_id: 18,
            });
        });

        it("When feature is moving at the first place, Then we get a position before the first feature", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 116 } as Feature;
            const features = [sibling, { id: 111 }, feature] as Feature[];
            const position = getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement(
                feature,
                sibling,
                features,
                4,
                18,
            );

            expect(position).toEqual({
                feature: { id: 115 },
                order: {
                    direction: "before",
                    compared_to: 116,
                },
                from_program_increment_id: 4,
                to_program_increment_id: 18,
            });
        });

        it("When sibling does not exist in the program increment, Then error is thrown", () => {
            const feature: Feature = { id: 115 } as Feature;
            const sibling: Feature = { id: 666 } as Feature;
            const features = [feature] as Feature[];
            expect(() =>
                getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement(
                    feature,
                    sibling,
                    features,
                    4,
                    18,
                ),
            ).toThrow("Cannot find feature with id #666");
        });

        it("When there are no features in program increment, Then FeatureReorder is null", () => {
            const feature: Feature = { id: 115 } as Feature;
            expect(
                getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement(
                    feature,
                    null,
                    [],
                    4,
                    18,
                ),
            ).toEqual({
                feature: { id: 115 },
                order: null,
                from_program_increment_id: 4,
                to_program_increment_id: 18,
            });
        });
    });

    describe("reorderFeatureInSameProgramIncrement", () => {
        let context: ActionContext<State, State>;
        beforeEach(() => {
            context = {
                commit: jest.fn(),
                state: {
                    program_increments: [
                        { id: 4, features: [{ id: 56 }, { id: 57 }, { id: 58 }] as Feature[] },
                    ] as ProgramIncrement[],
                } as State,
                getters: {},
            } as unknown as ActionContext<State, State>;
            context.getters = {
                getFeatureInProgramIncrement: getFeatureInProgramIncrement(context.state),
                getFeaturesInProgramIncrement: getFeaturesInProgramIncrement(context.state),
                getSiblingFeatureInProgramIncrement: getSiblingFeatureInProgramIncrement(
                    context.state,
                ),
            };
        });

        it("When no data element-id found, Then nothing happens", () => {
            const dropped_element = createElement();
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            reorderFeatureInSameProgramIncrement(
                context,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                } as HandleDropContextWithProgramId,
                4,
            );

            expect(context.commit).not.toHaveBeenCalled();
        });

        it(`When sibling is null, Then element is moving to the bottom`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "56");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const reorder_element_in_program_increment = jest.spyOn(
                featurePlanner,
                "reorderElementInProgramIncrement",
            );
            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(patch);

            await reorderFeatureInSameProgramIncrement(
                context,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                    program_id: 101,
                    next_sibling: null,
                },
                4,
            );

            const reorder_payload = {
                feature: { id: 56 },
                order: {
                    compared_to: 58,
                    direction: "after",
                },
                to_program_increment_id: 4,
            };

            expect(context.commit).toHaveBeenCalledWith(
                "changeFeaturePositionInSameProgramIncrement",
                reorder_payload,
            );

            expect(reorder_element_in_program_increment).toHaveBeenCalledWith(reorder_payload);
        });

        it(`When sibling is not null, Then element is moving to before the sibling`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "57");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const sibling_element = createElement();
            sibling_element.setAttribute("data-element-id", "56");

            const reorder_element_in_program_increment = jest.spyOn(
                featurePlanner,
                "reorderElementInProgramIncrement",
            );
            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchSuccess(patch);

            await reorderFeatureInSameProgramIncrement(
                context,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                    program_id: 101,
                    next_sibling: sibling_element,
                },
                4,
            );

            const reorder_payload = {
                feature: { id: 57 },
                order: {
                    direction: "before",
                    compared_to: 56,
                },
                to_program_increment_id: 4,
            };

            expect(context.commit).toHaveBeenCalledWith(
                "changeFeaturePositionInSameProgramIncrement",
                reorder_payload,
            );

            expect(reorder_element_in_program_increment).toHaveBeenCalledWith(reorder_payload);
        });

        it(`When sibling has not element-id data attribute, Then element is moving to the bottom`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "57");
            const source_dropzone = createElement();
            const target_dropzone = createElement();
            const sibling_element = createElement();

            const reorder_element_in_program_increment = jest.spyOn(
                featurePlanner,
                "reorderElementInProgramIncrement",
            );

            await reorderFeatureInSameProgramIncrement(
                context,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                    program_id: 101,
                    next_sibling: sibling_element,
                },
                4,
            );

            const reorder_payload = {
                feature: { id: 57 },
                order: {
                    direction: "after",
                    compared_to: 58,
                },
                to_program_increment_id: 4,
            };

            expect(context.commit).toHaveBeenCalledWith(
                "changeFeaturePositionInSameProgramIncrement",
                reorder_payload,
            );

            expect(reorder_element_in_program_increment).toHaveBeenCalledWith(reorder_payload);
        });

        it(`When error is thrown reordering elements, Then error is stored`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "57");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const sibling_element = createElement();
            sibling_element.setAttribute("data-element-id", "56");

            const reorder_element_in_program_increment = jest.spyOn(
                featurePlanner,
                "reorderElementInProgramIncrement",
            );
            const patch = jest.spyOn(tlp_fetch, "patch");
            mockFetchError(patch, {
                status: 404,
                error_json: { error: { code: 404, message: "Error" } },
            });

            await reorderFeatureInSameProgramIncrement(
                context,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                    program_id: 101,
                    next_sibling: sibling_element,
                },
                4,
            );

            const reorder_payload = {
                feature: { id: 57 },
                order: {
                    direction: "before",
                    compared_to: 56,
                },
                to_program_increment_id: 4,
            };

            expect(context.commit).toHaveBeenCalledWith(
                "changeFeaturePositionInSameProgramIncrement",
                reorder_payload,
            );

            expect(reorder_element_in_program_increment).toHaveBeenCalledWith(reorder_payload);
            expect(context.commit).toHaveBeenCalledWith("setModalErrorMessage", "404 Error");
        });
    });
});
