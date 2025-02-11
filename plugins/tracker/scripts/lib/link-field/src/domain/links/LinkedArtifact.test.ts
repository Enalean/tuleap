/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { LinkedArtifactStub } from "../../../tests/stubs/links/LinkedArtifactStub";
import { LinkTypeStub } from "../../../tests/stubs/links/LinkTypeStub";
import { LinkedArtifact } from "./LinkedArtifact";
import { LinkType } from "./LinkType";

describe(`LinkedArtifact`, () => {
    it(`builds a LinkedArtifact from an existing one with a new type`, () => {
        const link = LinkedArtifactStub.withIdAndType(46, LinkTypeStub.buildUntyped());
        const new_type = LinkTypeStub.buildParentLinkType();
        const changed_link = LinkedArtifact.fromLinkAndType(link, new_type);

        expect(LinkType.isForwardChild(changed_link.link_type)).toBe(true);
    });
});
