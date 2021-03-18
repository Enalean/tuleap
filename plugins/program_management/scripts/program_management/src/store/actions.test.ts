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

import * as actions from "./actions";
import type { State } from "../type";
import type { ActionContext } from "vuex";
import type {
    FeatureIdWithProgramIncrement,
    HandleDropContextWithProgramId,
} from "../helpers/drag-drop";
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";
import type { ToBePlannedElement } from "../helpers/ToBePlanned/element-to-plan-retriever";
import type { Feature } from "../helpers/ProgramIncrement/Feature/feature-retriever";
import type { FeatureIdToMoveFromProgramIncrementToAnother } from "../helpers/drag-drop";
import { createElement } from "../helpers/jest/create-dom-element";
import * as dragDrop from "../helpers/drag-drop";
import * as tlp from "tlp";
import * as backlogAdder from "../helpers/ProgramIncrement/add-to-top-backlog";

jest.mock("tlp");

describe("Actions", () => {
    let context: ActionContext<State, State>;
    beforeEach(() => {
        context = ({
            commit: jest.fn(),
            state: {} as State,
            getters: {},
        } as unknown) as ActionContext<State, State>;
    });

    describe("planFeatureInProgramIncrement", () => {
        it("When a feature is planned in a program increment, Then it is deleted from to be planned elements and add to increment", () => {
            const feature: ToBePlannedElement = { artifact_id: 125 } as ToBePlannedElement;
            const feature_id_with_increment: FeatureIdWithProgramIncrement = {
                feature_id: 125,
                program_increment: {
                    id: 4,
                    features: [{ artifact_id: 14 } as Feature],
                } as ProgramIncrement,
            };

            context.getters = { getToBePlannedElementFromId: (): ToBePlannedElement => feature };

            actions.planFeatureInProgramIncrement(context, feature_id_with_increment);

            expect(context.commit).toHaveBeenCalledWith("removeToBePlannedElement", feature);
            expect(feature_id_with_increment.program_increment.features.length).toEqual(2);
            expect(feature_id_with_increment.program_increment.features[0]).toEqual({
                artifact_id: 14,
            });
            expect(feature_id_with_increment.program_increment.features[1]).toEqual({
                artifact_id: 125,
            });
        });
    });

    describe("unplanFeatureFromProgramIncrement", () => {
        it("When feature does not exist, Then error is thrown", () => {
            const feature_id_with_increment: FeatureIdWithProgramIncrement = {
                feature_id: 125,
                program_increment: {
                    id: 4,
                    features: [{ artifact_id: 14 } as Feature],
                } as ProgramIncrement,
            };

            expect(() =>
                actions.unplanFeatureFromProgramIncrement(context, feature_id_with_increment)
            ).toThrowError("No feature with id #125 in program increment #4");
        });

        it("When feature exist, Then it is removed from increment and add in to be planned elements", () => {
            const feature_id_with_increment: FeatureIdWithProgramIncrement = {
                feature_id: 125,
                program_increment: {
                    id: 4,
                    features: [{ artifact_id: 125 } as Feature],
                } as ProgramIncrement,
            };

            actions.unplanFeatureFromProgramIncrement(context, feature_id_with_increment);
            expect(context.commit).toHaveBeenCalledWith("addToBePlannedElement", {
                artifact_id: 125,
            });
            expect(feature_id_with_increment.program_increment.features.length).toEqual(0);
        });
    });

    describe("moveFeatureFromProgramIncrementToAnother", () => {
        it("When feature does not exist, Then error is thrown", () => {
            const feature_id_with_increment: FeatureIdToMoveFromProgramIncrementToAnother = {
                feature_id: 125,
                from_program_increment: {
                    id: 4,
                    features: [{ artifact_id: 14 } as Feature],
                } as ProgramIncrement,
                to_program_increment: {
                    id: 5,
                    features: [] as Feature[],
                } as ProgramIncrement,
            };

            expect(() =>
                actions.moveFeatureFromProgramIncrementToAnother(context, feature_id_with_increment)
            ).toThrowError("No feature with id #125 in program increment #4");
        });

        it("When feature exist, Then it is removed from increment and add in to be planned elements", () => {
            const feature_id_with_increment: FeatureIdToMoveFromProgramIncrementToAnother = {
                feature_id: 125,
                from_program_increment: {
                    id: 4,
                    features: [{ artifact_id: 14 } as Feature, { artifact_id: 125 } as Feature],
                } as ProgramIncrement,
                to_program_increment: {
                    id: 5,
                    features: [] as Feature[],
                } as ProgramIncrement,
            };

            actions.moveFeatureFromProgramIncrementToAnother(context, feature_id_with_increment);
            expect(feature_id_with_increment.from_program_increment.features.length).toEqual(1);
            expect(feature_id_with_increment.from_program_increment.features[0]).toEqual({
                artifact_id: 14,
            });
            expect(feature_id_with_increment.to_program_increment.features.length).toEqual(1);
            expect(feature_id_with_increment.to_program_increment.features[0]).toEqual({
                artifact_id: 125,
            });
        });
    });

    describe(`handleDrop()`, () => {
        it(`Plan elements`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "14");
            const source_dropzone = createElement();
            const target_dropzone = createElement();
            target_dropzone.setAttribute("data-program-increment-id", "1");
            target_dropzone.setAttribute("data-artifact-link-field-id", "1234");
            target_dropzone.setAttribute("data-planned-feature-ids", "12,13");

            jest.spyOn(tlp, "put");
            const plan_feature = jest.spyOn(dragDrop, "planFeatureInProgramIncrement");

            const getProgramIncrementFromId = jest.fn().mockReturnValue({ id: 56, features: [] });
            const getToBePlannedElementFromId = jest.fn().mockReturnValue({ artifact_id: 125 });

            context.getters = { getProgramIncrementFromId, getToBePlannedElementFromId };

            await actions.handleDrop(context, {
                dropped_element,
                source_dropzone,
                target_dropzone,
                program_id: 101,
            } as HandleDropContextWithProgramId);

            expect(getProgramIncrementFromId).toHaveBeenCalledWith(1);
            expect(getToBePlannedElementFromId).toHaveBeenCalledWith(14);

            expect(context.commit).toHaveBeenCalledWith("removeToBePlannedElement", {
                artifact_id: 125,
            });

            expect(plan_feature).toHaveBeenCalledWith(
                {
                    dropped_element,
                    program_id: 101,
                    source_dropzone,
                    target_dropzone,
                },
                1,
                14
            );
        });

        it(`Removes elements from program increment`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            dropped_element.setAttribute("data-program-increment-id", "1");
            dropped_element.setAttribute("data-artifact-link-field-id", "1234");
            dropped_element.setAttribute("data-planned-feature-ids", "12,13");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const unplan_feature = jest.spyOn(dragDrop, "unplanFeature");
            jest.spyOn(backlogAdder, "addElementToTopBackLog");
            jest.spyOn(tlp, "put");

            const getProgramIncrementFromId = jest
                .fn()
                .mockReturnValue({ id: 56, features: [{ artifact_id: 12 }] });

            context.getters = { getProgramIncrementFromId };

            await actions.handleDrop(context, {
                dropped_element,
                source_dropzone,
                target_dropzone,
                program_id: 101,
            } as HandleDropContextWithProgramId);

            expect(getProgramIncrementFromId).toHaveBeenCalledWith(1);

            expect(context.commit).toHaveBeenCalledWith("addToBePlannedElement", {
                artifact_id: 12,
            });

            expect(unplan_feature).toHaveBeenCalledWith(
                {
                    dropped_element,
                    program_id: 101,
                    source_dropzone,
                    target_dropzone,
                },
                1,
                12
            );
        });

        it(`Moves elements from program increment to another`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            dropped_element.setAttribute("data-program-increment-id", "1");
            dropped_element.setAttribute("data-artifact-link-field-id", "1234");
            dropped_element.setAttribute("data-planned-feature-ids", "12,13");
            const source_dropzone = createElement();
            const target_dropzone = createElement();
            target_dropzone.setAttribute("data-program-increment-id", "2");
            target_dropzone.setAttribute("data-artifact-link-field-id", "3691");
            target_dropzone.setAttribute("data-planned-feature-ids", "125,126");

            const plan_feature = jest.spyOn(dragDrop, "planFeatureInProgramIncrement");
            const unplan_feature = jest.spyOn(dragDrop, "unplanFeature");
            jest.spyOn(tlp, "put");

            const getProgramIncrementFromId = jest
                .fn()
                .mockReturnValueOnce({ id: 1, features: [{ artifact_id: 12 }] } as ProgramIncrement)
                .mockReturnValueOnce({ id: 2, features: [] as Feature[] } as ProgramIncrement);

            context.getters = { getProgramIncrementFromId };

            await actions.handleDrop(context, {
                dropped_element,
                source_dropzone,
                target_dropzone,
                program_id: 101,
            } as HandleDropContextWithProgramId);

            expect(getProgramIncrementFromId).toHaveBeenNthCalledWith(1, 1);
            expect(getProgramIncrementFromId).toHaveBeenNthCalledWith(2, 2);

            expect(unplan_feature).toHaveBeenCalledWith(
                {
                    dropped_element,
                    program_id: 101,
                    source_dropzone,
                    target_dropzone,
                },
                1,
                12
            );
            expect(plan_feature).toHaveBeenCalledWith(
                {
                    dropped_element,
                    program_id: 101,
                    source_dropzone,
                    target_dropzone,
                },
                2,
                12
            );
        });
    });
});
