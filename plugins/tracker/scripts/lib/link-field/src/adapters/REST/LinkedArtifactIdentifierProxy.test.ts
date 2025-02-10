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
import type { ArtifactWithStatus } from "./ArtifactWithStatus";
import { LinkedArtifactIdentifierProxy } from "./LinkedArtifactIdentifierProxy";

const ARTIFACT_ID = 48;

describe(`LinkedArtifactIdentifierFactory`, () => {
    it(`builds an identifier from an Artifact representation from the API`, () => {
        const api_artifact = { id: ARTIFACT_ID } as ArtifactWithStatus;

        const linked_artifact_id = LinkedArtifactIdentifierProxy.fromAPIArtifact(api_artifact);
        expect(linked_artifact_id.id).toBe(ARTIFACT_ID);
    });
});
