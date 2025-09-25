/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { LinkedArtifactStub } from "@/sections/stubs/readonly-fields/LinkedArtifactStub";
import {
    FORWARD_DIRECTION,
    IS_CHILD_LINK_TYPE,
    REVERSE_DIRECTION,
    DEFAULT_LINK_TYPE,
} from "@tuleap/plugin-tracker-constants";
import { sortLinkedArtifacts } from "@/components/section/readonly-fields/sort-linked-artifacts";

describe("sort-linked-artifacts", () => {
    const parent_1 = LinkedArtifactStub.override({
        link_type: { shortname: IS_CHILD_LINK_TYPE, direction: REVERSE_DIRECTION },
    });
    const parent_2 = LinkedArtifactStub.override({
        link_type: { shortname: IS_CHILD_LINK_TYPE, direction: REVERSE_DIRECTION },
    });
    const link_1 = LinkedArtifactStub.override({
        link_type: { shortname: DEFAULT_LINK_TYPE, direction: REVERSE_DIRECTION },
    });
    const link_2 = LinkedArtifactStub.override({
        link_type: { shortname: DEFAULT_LINK_TYPE, direction: FORWARD_DIRECTION },
    });
    const link_3 = LinkedArtifactStub.override({
        link_type: { shortname: "_duplicates", direction: FORWARD_DIRECTION },
    });
    const link_5 = LinkedArtifactStub.override({
        link_type: { shortname: "_fixed_in", direction: REVERSE_DIRECTION },
    });

    it("Should place parent artifacts first in the list", () => {
        const unsorted_links = [link_1, link_2, link_3, parent_1, link_5, parent_2];

        expect(sortLinkedArtifacts(unsorted_links)).toStrictEqual([
            parent_1,
            parent_2,
            link_1,
            link_2,
            link_3,
            link_5,
        ]);
    });

    it("Should return the links in their natual order when there is no parent artifact", () => {
        const unsorted_links = [link_1, link_2, link_3, link_5];

        expect(sortLinkedArtifacts(unsorted_links)).toStrictEqual([link_1, link_2, link_3, link_5]);
    });

    it("Should return an empty array when there is no linked artifact", () => {
        expect(sortLinkedArtifacts([])).toStrictEqual([]);
    });
});
