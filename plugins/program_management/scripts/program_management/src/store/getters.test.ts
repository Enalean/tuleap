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

import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";
import type { State } from "../type";
import * as getters from "./getters";
import type { Feature } from "../helpers/ProgramIncrement/Feature/feature-retriever";
import type { ToBePlannedElement } from "../helpers/ToBePlanned/element-to-plan-retriever";

describe("Getters", () => {
    describe("isProgramIncrementAlreadyAdded", () => {
        it("When program increment already exists, Then true is returned", () => {
            const state = {
                program_increments: [
                    {
                        id: 14,
                    } as ProgramIncrement,
                ],
            } as State;

            expect(getters.isProgramIncrementAlreadyAdded(state)(14)).toBeTruthy();
        });

        it("When program increment does not exist, Then false is returned", () => {
            const state = {
                program_increments: [
                    {
                        id: 14,
                    } as ProgramIncrement,
                ],
            } as State;

            expect(getters.isProgramIncrementAlreadyAdded(state)(250)).toBeFalsy();
        });
    });

    describe("getFeaturesInProgramIncrement", () => {
        it("When program increment does not exist, Then error is thrown", () => {
            const state = {
                program_increments: [] as ProgramIncrement[],
            } as State;

            expect(() => getters.getFeaturesInProgramIncrement(state)(14)).toThrowError(
                "No program increment with id #14"
            );
        });

        it("When program increment exists, Then its features are returned", () => {
            const state = {
                program_increments: [
                    {
                        id: 14,
                        features: [{ artifact_id: 56 }] as Feature[],
                    } as ProgramIncrement,
                ],
            } as State;

            expect(getters.getFeaturesInProgramIncrement(state)(14)).toEqual([{ artifact_id: 56 }]);
        });
    });

    describe("getToBePlannedElementFromId", () => {
        it("When to be planned element does not exist, Then error is thrown", () => {
            const state = {
                to_be_planned_elements: [] as ToBePlannedElement[],
            } as State;

            expect(() => getters.getToBePlannedElementFromId(state)(14)).toThrowError(
                "No to be planned element with id #14"
            );
        });

        it("When to be planned element exist in state, Then it is returned", () => {
            const state = {
                to_be_planned_elements: [{ artifact_id: 14 }] as ToBePlannedElement[],
            } as State;

            expect(getters.getToBePlannedElementFromId(state)(14)).toEqual({ artifact_id: 14 });
        });
    });
});
