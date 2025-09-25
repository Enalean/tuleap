/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { LinkType } from "../../../src/domain/links/LinkType";
import {
    FORWARD_DIRECTION,
    IS_CHILD_LINK_TYPE,
    MIRRORED_MILESTONE_LINK_TYPE,
    REVERSE_DIRECTION,
    DEFAULT_LINK_TYPE,
} from "@tuleap/plugin-tracker-constants";

const CUSTOM_TYPE = "custom";
export const LinkTypeStub = {
    buildDefaultLinkType: LinkType.buildDefaultLinkType,

    buildLinkedFromLinkType: (): LinkType => ({
        shortname: DEFAULT_LINK_TYPE,
        direction: REVERSE_DIRECTION,
        label: "is Linked from",
    }),
    buildParentLinkType: (): LinkType => ({
        shortname: IS_CHILD_LINK_TYPE,
        direction: FORWARD_DIRECTION,
        label: "is Parent of",
    }),
    buildChildLinkType: (): LinkType => ({
        shortname: IS_CHILD_LINK_TYPE,
        direction: REVERSE_DIRECTION,
        label: "is Child of",
    }),
    buildMirrors: (): LinkType => ({
        shortname: MIRRORED_MILESTONE_LINK_TYPE,
        direction: FORWARD_DIRECTION,
        label: "Mirrors",
    }),
    buildMirroredBy: (): LinkType => ({
        shortname: MIRRORED_MILESTONE_LINK_TYPE,
        direction: REVERSE_DIRECTION,
        label: "Mirrored by",
    }),
    buildForwardCustom: (): LinkType => ({
        shortname: CUSTOM_TYPE,
        direction: FORWARD_DIRECTION,
        label: "Custom Forward",
    }),
    buildReverseCustom: (): LinkType => ({
        shortname: CUSTOM_TYPE,
        direction: REVERSE_DIRECTION,
        label: "Custom Reverse",
    }),
};
