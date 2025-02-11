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
import type { Fault } from "@tuleap/fault";
import type { RetrieveLinkTypes } from "../../../src/domain/links/RetrieveLinkTypes";
import type { LinkType } from "../../../src/domain/links/LinkType";

export const RetrieveLinkTypesStub = {
    withTypes: (
        link_type: LinkType,
        ...other_link_types: readonly LinkType[]
    ): RetrieveLinkTypes => {
        const types = [link_type, ...other_link_types];
        return {
            getAllLinkTypes: () => okAsync(types),
        };
    },

    withFault: (fault: Fault): RetrieveLinkTypes => ({
        getAllLinkTypes: () => errAsync(fault),
    }),
};
