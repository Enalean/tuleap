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
import type { ToBePlannedElement } from "../helpers/ToBePlanned/element-to-plan-retriever";
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";

export default {
    addProgramIncrement(state: State, program_increment: ProgramIncrement): void {
        const existing_increment = state.program_increments.find(
            (existing_increment) => existing_increment.id === program_increment.id
        );

        if (existing_increment !== undefined) {
            throw Error("Program increment with id #" + program_increment.id + " already exists");
        }

        state.program_increments.push(program_increment);
    },

    setToBePlannedElements(state: State, to_be_planned_elements: ToBePlannedElement[]): void {
        state.to_be_planned_elements = to_be_planned_elements;
    },
};
