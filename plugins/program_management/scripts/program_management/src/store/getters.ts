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
import type { Feature } from "../helpers/ProgramIncrement/Feature/feature-retriever";

export const isProgramIncrementAlreadyAdded = (state: State) => (increment_id: number): boolean => {
    return (
        state.program_increments.find((increment) => increment.id === increment_id) !== undefined
    );
};

export const getFeaturesInProgramIncrement = (state: State) => (
    increment_id: number
): Feature[] => {
    const program_increment = state.program_increments.find(
        (increment) => increment.id === increment_id
    );

    if (!program_increment) {
        throw Error("No program increment with id #" + increment_id);
    }

    return program_increment.features;
};
