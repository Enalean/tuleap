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
import type { Feature, State } from "../type";
import * as getters from "./getters";

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
                        features: [{ id: 56 }] as Feature[],
                    } as ProgramIncrement,
                ],
            } as State;

            expect(getters.getFeaturesInProgramIncrement(state)(14)).toEqual([{ id: 56 }]);
        });
    });

    describe("getToBePlannedElementFromId", () => {
        it("When to be planned element does not exist, Then error is thrown", () => {
            const state = {
                to_be_planned_elements: [] as Feature[],
            } as State;

            expect(() => getters.getToBePlannedElementFromId(state)(14)).toThrowError(
                "No to be planned element with id #14"
            );
        });

        it("When to be planned element exist in state, Then it is returned", () => {
            const state = {
                to_be_planned_elements: [{ id: 14 }] as Feature[],
            } as State;

            expect(getters.getToBePlannedElementFromId(state)(14)).toEqual({ id: 14 });
        });
    });

    describe("hasAnElementMovedInsideIncrement", () => {
        it("When there are not elements moving, Then return false", () => {
            const state = {
                ongoing_move_elements_id: [] as number[],
            } as State;

            expect(getters.hasAnElementMovedInsideIncrement(state)).toEqual(false);
        });
        it("When there are elements moving, Then return true", () => {
            const state = {
                ongoing_move_elements_id: [14],
            } as State;

            expect(getters.hasAnElementMovedInsideIncrement(state)).toEqual(true);
        });
    });
});
