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

import { getLinkableArtifact } from "./LinkableArtifactTemplate";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";

describe(`LinkableArtifactTemplate`, () => {
    describe(`getLinkableArtifact`, () => {
        it(`will return null when a given item does not look like a Linkable Artifact`, () => {
            const item = { not_a_linkable_artifact: true };
            expect(getLinkableArtifact(item)).toBeNull();
        });

        it(`will return the item when it looks like a Linkable Artifact`, () => {
            const item = LinkableArtifactStub.withDefaults();
            expect(getLinkableArtifact(item)).toBe(item);
        });
    });
});
