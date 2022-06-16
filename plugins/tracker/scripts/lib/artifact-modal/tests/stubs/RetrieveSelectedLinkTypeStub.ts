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

import type { RetrieveSelectedLinkType } from "../../src/domain/fields/link-field/RetrieveSelectedLinkType";
import type { LinkType } from "../../src/domain/fields/link-field/LinkType";

export const RetrieveSelectedLinkTypeStub = {
    withType: (type: LinkType): RetrieveSelectedLinkType => ({
        getSelectedLinkType: () => type,
    }),

    withSuccessiveTypes: (
        type: LinkType,
        ...other_types: readonly LinkType[]
    ): RetrieveSelectedLinkType => {
        const all_batches = [type, ...other_types];
        return {
            getSelectedLinkType(): LinkType {
                const batch = all_batches.shift();
                if (batch !== undefined) {
                    return batch;
                }
                throw new Error("No link type configured");
            },
        };
    },
};
