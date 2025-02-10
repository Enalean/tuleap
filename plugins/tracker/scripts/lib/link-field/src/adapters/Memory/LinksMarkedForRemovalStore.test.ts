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

import { describe, expect, it } from "vitest";
import { LinksMarkedForRemovalStore } from "./LinksMarkedForRemovalStore";
import { LinkedArtifactStub } from "../../../tests/stubs/links/LinkedArtifactStub";
import { LinkedArtifactIdentifierStub } from "../../../tests/stubs/links/LinkedArtifactIdentifierStub";

describe(`LinksMarkedForRemovalStore`, () => {
    it(`adds, checks and deletes links marked for removal`, () => {
        const store = LinksMarkedForRemovalStore();

        const identifier = LinkedArtifactIdentifierStub.withId(54);
        const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
        store.addLinkMarkedForRemoval(identifier);

        expect(store.isMarkedForRemoval(linked_artifact)).toBe(true);

        store.deleteLinkMarkedForRemoval(identifier);

        expect(store.isMarkedForRemoval(linked_artifact)).toBe(false);
    });
});
