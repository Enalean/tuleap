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

import type { LinkDirection } from "@tuleap/plugin-tracker-constants";
import {
    FORWARD_DIRECTION,
    IS_CHILD_LINK_TYPE,
    MIRRORED_MILESTONE_LINK_TYPE,
    REVERSE_DIRECTION,
    DEFAULT_LINK_TYPE,
} from "@tuleap/plugin-tracker-constants";

export interface LinkType {
    readonly shortname: string;
    readonly direction: LinkDirection;
    readonly label: string;
}

export const LinkType = {
    buildDefaultLinkType: (): LinkType => ({
        shortname: DEFAULT_LINK_TYPE,
        direction: FORWARD_DIRECTION,
        label: "",
    }),

    isForwardChild: (type: LinkType): boolean =>
        type.shortname === IS_CHILD_LINK_TYPE && type.direction === FORWARD_DIRECTION,

    isReverseChild: (type: LinkType): boolean =>
        type.shortname === IS_CHILD_LINK_TYPE && type.direction === REVERSE_DIRECTION,

    isDefaultTypeLabel: (type: LinkType): boolean => type.shortname === DEFAULT_LINK_TYPE,

    isMirroredMilestone: (type: LinkType): boolean =>
        type.shortname === MIRRORED_MILESTONE_LINK_TYPE,
};
