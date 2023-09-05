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

import { UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import { FORWARD_DIRECTION, LinkType } from "./LinkType";
import { LinkTypeStub } from "../../../../tests/stubs/LinkTypeStub";

describe(`LinkType`, () => {
    it(`builds the "Untyped" link type`, () => {
        const type = LinkType.buildUntyped();
        expect(type.shortname).toBe(UNTYPED_LINK);
        expect(type.direction).toBe(FORWARD_DIRECTION);
        expect(type.label).toBe("");
    });

    it.each([
        [true, "reverse _is_child", LinkTypeStub.buildChildLinkType()],
        [false, "forward _is_child", LinkTypeStub.buildParentLinkType()],
        [false, "untyped", LinkTypeStub.buildUntyped()],
    ])(
        `isReverseChild() returns %s when given a %s link type`,
        (expected_return, link_type_string, link_type) => {
            expect(LinkType.isReverseChild(link_type)).toBe(expected_return);
        },
    );

    it.each([
        [true, "forward _is_child", LinkTypeStub.buildParentLinkType()],
        [false, "reverse _is_child", LinkTypeStub.buildChildLinkType()],
        [false, "untyped", LinkTypeStub.buildUntyped()],
    ])(
        `isForwardChild() returns %s when given a %s link type`,
        (expected_return, link_type_string, link_type) => {
            expect(LinkType.isForwardChild(link_type)).toBe(expected_return);
        },
    );

    it.each([
        [true, "forward _mirrored_milestone", LinkTypeStub.buildMirrors()],
        [true, "reverse _mirrored_milestone", LinkTypeStub.buildMirroredBy()],
        [false, "untyped", LinkTypeStub.buildUntyped()],
    ])(
        `isMirroredMilestone() returns %s when given a %s link type`,
        (expected_return, link_type_string, link_type) => {
            expect(LinkType.isMirroredMilestone(link_type)).toBe(expected_return);
        },
    );
});
