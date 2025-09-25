/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import type { LinkTypeRepresentation } from "@tuleap/plugin-tracker-rest-api-types";
import {
    FORWARD_DIRECTION,
    IS_CHILD_LINK_TYPE,
    DEFAULT_LINK_TYPE,
    REVERSE_DIRECTION,
} from "@tuleap/plugin-tracker-constants";

export const LinkTypeRepresentationBuilder = {
    buildLinkedTo: (): LinkTypeRepresentation => ({
        shortname: DEFAULT_LINK_TYPE,
        direction: FORWARD_DIRECTION,
        label: "Linked to",
    }),
    buildLinkedFrom: (): LinkTypeRepresentation => ({
        shortname: DEFAULT_LINK_TYPE,
        direction: REVERSE_DIRECTION,
        label: "Linked from",
    }),
    buildParentOf: (): LinkTypeRepresentation => ({
        shortname: IS_CHILD_LINK_TYPE,
        direction: FORWARD_DIRECTION,
        label: "Child",
    }),
    buildChildOf: (): LinkTypeRepresentation => ({
        shortname: IS_CHILD_LINK_TYPE,
        direction: REVERSE_DIRECTION,
        label: "Parent",
    }),
};
