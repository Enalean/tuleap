/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { LinkableArtifactRESTFilter } from "./LinkableArtifactRESTFilter";
import { ARTIFACT_TYPE } from "@tuleap/core-rest-api-types";
import type { SearchResultEntry } from "@tuleap/core-rest-api-types/src/main";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";

describe("LinkableArtifactRESTFilter", () => {
    const ARTIFACT_ID = 12;
    const OTHER_ARTIFACT_ID = 8545;

    describe("filterArtifact", () => {
        it("returns true when the current entry is an artifact and the modal is in creation mode", () => {
            const entry = {
                per_type_id: ARTIFACT_ID,
                type: ARTIFACT_TYPE,
                badges: [],
            } as unknown as SearchResultEntry;
            const current_artifact = null;

            expect(LinkableArtifactRESTFilter.filterArtifact(entry, current_artifact)).toBe(true);
        });

        it("returns false when the current entry is NOT an artifact and the modal is in creation mode", () => {
            const entry = {
                per_type_id: ARTIFACT_ID,
                type: "song",
                badges: [],
            } as unknown as SearchResultEntry;
            const current_artifact = null;
            expect(LinkableArtifactRESTFilter.filterArtifact(entry, current_artifact)).toBe(false);
        });

        it("returns false when the current entry is the same artifact as the current edited artifact", () => {
            const entry = {
                per_type_id: ARTIFACT_ID,
                type: ARTIFACT_TYPE,
                badges: [],
            } as unknown as SearchResultEntry;
            const current_artifact = CurrentArtifactIdentifierStub.withId(ARTIFACT_ID);
            expect(LinkableArtifactRESTFilter.filterArtifact(entry, current_artifact)).toBe(false);
        });

        it("returns true when the current entry is not the same artifact as the current edited artifact", () => {
            const entry = {
                per_type_id: OTHER_ARTIFACT_ID,
                type: ARTIFACT_TYPE,
                badges: [],
            } as unknown as SearchResultEntry;
            const current_artifact = CurrentArtifactIdentifierStub.withId(ARTIFACT_ID);
            expect(LinkableArtifactRESTFilter.filterArtifact(entry, current_artifact)).toBe(true);
        });
    });
});
