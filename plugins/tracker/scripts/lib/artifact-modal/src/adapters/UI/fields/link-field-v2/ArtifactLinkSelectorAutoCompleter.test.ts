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
import type { RetrieveMatchingArtifact } from "../../../../domain/fields/link-field-v2/RetrieveMatchingArtifact";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import { LinkSelectorStub } from "../../../../../tests/stubs/LinkSelectorStub";
import type { LinkableArtifact } from "../../../../domain/fields/link-field-v2/LinkableArtifact";

const ARTIFACT_ID = 1621;

describe("ArtifactLinkSelectorAutoCompleter", () => {
    let artifact: LinkableArtifact,
        artifact_retriever: RetrieveMatchingArtifact,
        link_selector: LinkSelectorStub,
        current_artifact_identifier: CurrentArtifactIdentifier | null;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        artifact = LinkableArtifactStub.withCrossReference(
            ARTIFACT_ID,
            "Do some stuff",
            `story #${ARTIFACT_ID}`,
            "army-green"
        );
        artifact_retriever = RetrieveMatchingArtifactStub.withMatchingArtifact(artifact);
        link_selector = LinkSelectorStub.withDropdownContentRecord();
        current_artifact_identifier = null;
    });

    const autocomplete = async (query: string): Promise<void> => {
        const autocompleter = ArtifactLinkSelectorAutoCompleter(
            artifact_retriever,
            current_artifact_identifier
        );
        await autocompleter.autoComplete(link_selector, query);
    };

    it.each([
        ["an empty string", ""],
        ["not a number", "I know I'm supposed to enter a number by I don't care"],
    ])(
        "when the query is %s, it will set an empty group collection in link-selector",
        async (query_content_type: string, query: string) => {
            await autocomplete(query);
            const groups = link_selector.getGroupCollection();
            expect(groups).toHaveLength(0);
        }
    );

    it(`when an artifact is returned by the api,
        then it will set a group with one item holding the matching artifact`, async () => {
        await autocomplete(String(ARTIFACT_ID));
        const groups = link_selector.getGroupCollection();

        if (groups === undefined) {
            throw new Error("Expected a group collection to be set");
        }
        expect(groups).toHaveLength(1);
        expect(groups[0].items).toHaveLength(1);

        const first_item = groups[0].items[0];
        expect(first_item.value).toBe(artifact);
    });

    it(`when an error is returned by the api,
        then it will set a group with zero items so that link-selector can show the empty state message`, async () => {
        const fault = Fault.fromMessage("Nope");
        artifact_retriever = RetrieveMatchingArtifactStub.withFault(fault);

        await autocomplete(String(ARTIFACT_ID));
        const groups = link_selector.getGroupCollection();

        if (groups === undefined) {
            throw new Error("Expected a group collection to be set");
        }
        expect(groups).toHaveLength(1);
        expect(groups[0].items).toHaveLength(0);
        expect(groups[0].empty_message).not.toBe("");
    });
});
