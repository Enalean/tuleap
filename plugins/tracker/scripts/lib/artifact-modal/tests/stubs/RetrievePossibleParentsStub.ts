/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { okAsync, errAsync } from "neverthrow";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { RetrievePossibleParents } from "../../src/domain/fields/link-field/RetrievePossibleParents";
import type { LinkableArtifact } from "../../src/domain/fields/link-field/LinkableArtifact";

export const RetrievePossibleParentsStub = {
    withParents: (
        possible_parents: ResultAsync<readonly LinkableArtifact[], never>,
    ): RetrievePossibleParents => ({
        getPossibleParents: () => possible_parents,
    }),

    withoutParents: (): RetrievePossibleParents => ({
        getPossibleParents: () => okAsync([]),
    }),

    withSuccessiveParents: (
        possible_parents: readonly LinkableArtifact[],
        ...other_parents: readonly LinkableArtifact[][]
    ): RetrievePossibleParents => {
        const all_batches = [possible_parents, ...other_parents];
        return {
            getPossibleParents: (): ResultAsync<readonly LinkableArtifact[], Fault> => {
                const batch = all_batches.shift();
                if (batch !== undefined) {
                    return okAsync(batch);
                }
                throw new Error("No possible parents configured");
            },
        };
    },

    withFault: (fault: Fault): RetrievePossibleParents => ({
        getPossibleParents: () => errAsync(fault),
    }),
};
