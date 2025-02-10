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
import type { UserHistoryEntry } from "@tuleap/core-rest-api-types";
import { ArtifactCrossReferenceProxy } from "./ArtifactCrossReferenceProxy";
import type { ArtifactWithStatus } from "./ArtifactWithStatus";

const CROSS_REFERENCE = "bug #247";
const COLOR = "coral-pink";

describe(`ArtifactCrossReferenceProxy`, () => {
    it(`builds from an artifact JSON payload from the REST API`, () => {
        const response = {
            xref: CROSS_REFERENCE,
            tracker: { color_name: COLOR },
        } as ArtifactWithStatus;
        const reference = ArtifactCrossReferenceProxy.fromAPIArtifact(response);

        expect(reference.ref).toBe(CROSS_REFERENCE);
        expect(reference.color).toBe(COLOR);
    });

    it(`builds from a History entry representation from the API`, () => {
        const entry = {
            xref: CROSS_REFERENCE,
            color_name: COLOR,
        } as UserHistoryEntry;
        const reference = ArtifactCrossReferenceProxy.fromAPIUserHistory(entry);

        expect(reference.ref).toBe(CROSS_REFERENCE);
        expect(reference.color).toBe(COLOR);
    });
});
