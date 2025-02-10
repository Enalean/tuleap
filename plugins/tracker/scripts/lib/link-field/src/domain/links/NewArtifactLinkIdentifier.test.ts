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
import { NewArtifactLinkIdentifier } from "./NewArtifactLinkIdentifier";
import { LinkableArtifactStub } from "../../../tests/stubs/links/LinkableArtifactStub";

const ARTIFACT_ID = 77;

describe(`NewArtifactLinkIdentifier`, () => {
    it(`builds an identifier from a LinkableArtifact`, () => {
        const linkable_artifact = LinkableArtifactStub.withDefaults({ id: ARTIFACT_ID });

        const identifier = NewArtifactLinkIdentifier.fromLinkableArtifact(linkable_artifact);
        expect(identifier.id).toBe(ARTIFACT_ID);
    });
});
