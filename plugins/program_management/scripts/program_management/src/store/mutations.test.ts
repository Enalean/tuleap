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

import type { State } from "../type";
import mutations from "./mutations";
import type { Feature } from "../helpers/ProgramIncrement/Feature/feature-retriever";
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";

describe("Mutations", () => {
    describe("addProgramIncrement", () => {
        it("When there is the same program increment in state, Then error is thrown", () => {
            const state = {
                program_increments: [
                    {
                        id: 14,
                        features: [{ artifact_id: 588 } as Feature],
                    } as ProgramIncrement,
                ],
            } as State;

            const program_increment = {
                id: 14,
            } as ProgramIncrement;

            expect(() => mutations.addProgramIncrement(state, program_increment)).toThrowError(
                "Program increment with id #14 already exists"
            );
        });

        it("When program increment does not exist in state, Then it is added", () => {
            const state = {
                program_increments: [
                    {
                        id: 15,
                    } as ProgramIncrement,
                ],
            } as State;

            const program_increment = {
                id: 14,
                features: [{ artifact_id: 588 } as Feature],
            } as ProgramIncrement;

            mutations.addProgramIncrement(state, program_increment);
            expect(state.program_increments.length).toEqual(2);
            expect(state.program_increments[0]).toEqual({ id: 15 });
            expect(state.program_increments[1]).toEqual({
                id: 14,
                features: [{ artifact_id: 588 }],
            });
        });
    });
});
