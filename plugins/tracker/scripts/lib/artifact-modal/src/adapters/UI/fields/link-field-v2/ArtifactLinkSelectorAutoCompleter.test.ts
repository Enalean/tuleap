/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { setCatalog } from "../../../../gettext-catalog";
import { ArtifactLinkSelectorAutoCompleter } from "./ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { Fault } from "@tuleap/fault";
import type { GroupCollection } from "@tuleap/link-selector";
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field-v2/RetrieveMatchingArtifact";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";

const ARTIFACT_ID = 1621;

describe("ArtifactLinkSelectorAutoCompleter", () => {
    let artifact_retriever: RetrieveMatchingArtifact,
        current_artifact_identifier: CurrentArtifactIdentifier | null;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        const artifact = {
            id: ARTIFACT_ID,
            title: "Do some stuff",
            xref: `story #${ARTIFACT_ID}`,
        };
        artifact_retriever = RetrieveMatchingArtifactStub.withMatchingArtifact(artifact);
        current_artifact_identifier = null;
    });

    const autocomplete = (query: string): Promise<GroupCollection> => {
        const autocompleter = ArtifactLinkSelectorAutoCompleter(
            artifact_retriever,
            current_artifact_identifier
        );
        return autocompleter.autoComplete()(query, jest.fn());
    };

    it.each([
        ["an empty string", ""],
        ["not a number", "I know I'm supposed to enter a number by I don't care"],
    ])(
        "will return an empty group collection when the query is %s",
        async (query_content_type: string, query: string) => {
            const groups = await autocomplete(query);

            expect(groups).toHaveLength(0);
        }
    );

    it(`when an artifact is returned by the api,
        then it will return a group with one item holding the matching artifact`, async () => {
        const groups = await autocomplete(String(ARTIFACT_ID));

        expect(groups).toHaveLength(1);
        expect(groups[0].items).toHaveLength(1);

        const first_item = groups[0].items[0];

        expect(first_item.value).toBe(String(ARTIFACT_ID));
    });

    it(`when an error is returned by the api,
        then it will return a group with zero items so that link-selector can show the empty state message`, async () => {
        const fault = Fault.fromMessage("Nope");
        artifact_retriever = RetrieveMatchingArtifactStub.withFault(fault);
        const groups = await autocomplete(String(ARTIFACT_ID));

        expect(groups).toHaveLength(1);
        expect(groups[0].items).toHaveLength(0);
        expect(groups[0].empty_message).not.toBe("");
    });
});
