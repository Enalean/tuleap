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
import { ARTIFACT_TYPE, KANBAN_TYPE } from "@tuleap/plugin-tracker-constants";
import type { SearchResultEntry } from "@tuleap/core-rest-api-types/src/main";
import { Option } from "@tuleap/option";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";

describe("LinkableArtifactRESTFilter", () => {
    let current_artifact_option: Option<CurrentArtifactIdentifier>, entry: SearchResultEntry;
    const ARTIFACT_ID = 12,
        OTHER_ARTIFACT_ID = 8545;

    beforeEach(() => {
        entry = buildSearchResultEntryStub(ARTIFACT_ID, ARTIFACT_TYPE);
        current_artifact_option = Option.nothing();
    });

    const filter = (): boolean =>
        LinkableArtifactRESTFilter.filterArtifact(entry, current_artifact_option);

    describe("filterArtifact", () => {
        it("returns true when the current entry is an artifact and the modal is in creation mode", () => {
            expect(filter()).toBe(true);
        });

        it("returns false when the current entry is NOT an artifact and the modal is in creation mode", () => {
            entry = buildSearchResultEntryStub(41, KANBAN_TYPE);
            expect(filter()).toBe(false);
        });

        it("returns false when the current entry is the same artifact as the current edited artifact", () => {
            current_artifact_option = Option.fromValue(
                CurrentArtifactIdentifierStub.withId(ARTIFACT_ID),
            );
            expect(filter()).toBe(false);
        });

        it("returns true when the current entry is not the same artifact as the current edited artifact", () => {
            entry = buildSearchResultEntryStub(OTHER_ARTIFACT_ID, ARTIFACT_TYPE);
            current_artifact_option = Option.fromValue(
                CurrentArtifactIdentifierStub.withId(ARTIFACT_ID),
            );
            expect(filter()).toBe(true);
        });
    });
});

function buildSearchResultEntryStub(
    per_type_id: number,
    type: "kanban" | "artifact",
): SearchResultEntry {
    const project = { id: 102, label: "Cloth Decide", icon: "" };
    return {
        per_type_id,
        type,
        project,
    } as SearchResultEntry;
}
