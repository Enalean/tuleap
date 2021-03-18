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
import type { ProgramElement, State } from "../type";
import type { ActionContext } from "vuex";
import type { FeatureIdWithProgramIncrement } from "../helpers/drag-drop";
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";
import type { Feature } from "../helpers/ProgramIncrement/Feature/feature-retriever";
import type { FeatureIdToMoveFromProgramIncrementToAnother } from "../helpers/drag-drop";

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
            const feature: ProgramElement = { artifact_id: 125 } as ProgramElement;
            const feature_id_with_increment: FeatureIdWithProgramIncrement = {
                feature_id: 125,
                program_increment: {
                    id: 4,
                    features: [{ artifact_id: 14 } as Feature],
                } as ProgramIncrement,
            };

            context.getters = { getToBePlannedElementFromId: (): ProgramElement => feature };

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
});
