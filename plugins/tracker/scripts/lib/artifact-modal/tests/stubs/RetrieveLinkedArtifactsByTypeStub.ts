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

import type { LinkedArtifact } from "../../src/domain/fields/link-field-v2/LinkedArtifact";
import type { RetrieveLinkedArtifactsByType } from "../../src/domain/fields/link-field-v2/RetrieveLinkedArtifactsByType";

export const RetrieveLinkedArtifactsByTypeStub = {
    withSuccessiveLinkedArtifacts: (
        first_batch: LinkedArtifact[],
        ...other_batches: LinkedArtifact[][]
    ): RetrieveLinkedArtifactsByType => {
        const all_batches = [first_batch, ...other_batches];
        return {
            getLinkedArtifactsByLinkType: (): Promise<LinkedArtifact[]> => {
                const batch = all_batches.shift();
                if (batch !== undefined) {
                    return Promise.resolve(batch);
                }
                throw new Error("No linked artifacts configured");
            },
        };
    },

    withError: (error_message: string): RetrieveLinkedArtifactsByType => ({
        getLinkedArtifactsByLinkType: (): Promise<never> =>
            Promise.reject(new Error(error_message)),
    }),
};
