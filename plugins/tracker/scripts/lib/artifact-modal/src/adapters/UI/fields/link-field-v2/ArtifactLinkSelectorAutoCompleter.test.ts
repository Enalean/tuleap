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
import type { Artifact } from "../../../../domain/Artifact";

describe("ArtifactLinkSelectorAutoCompleter", () => {
    let select: HTMLSelectElement, doc: Document;

    function populateSelect(): void {
        const option = doc.createElement("option");
        option.value = "666";
        option.textContent = "story #666 - A story from Hell";

        select.appendChild(option);
    }

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });

        doc = document.implementation.createHTMLDocument();
        select = doc.createElement("select");

        populateSelect();
    });

    it.each([
        ["an empty string", ""],
        ["not a number", "I know I'm supposed to enter a number by I don't care"],
    ])(
        "should clear the source select options when the query is %s",
        async (query_content_type: string, query: string) => {
            const autocompleter = ArtifactLinkSelectorAutoCompleter(
                RetrieveMatchingArtifactStub.withMatchingArtifact({} as unknown as Artifact),
                null
            );

            await autocompleter.autoComplete(select)(query);

            expect(select.options).toHaveLength(0);
        }
    );

    it("when an artifact is returned by the api, then an option is added to the source select", async () => {
        const artifact_id = 1621;
        const artifact = {
            id: artifact_id,
            title: "Do some stuff",
            xref: "story #1621",
        };

        const autocompleter = ArtifactLinkSelectorAutoCompleter(
            RetrieveMatchingArtifactStub.withMatchingArtifact(artifact),
            null
        );

        await autocompleter.autoComplete(select)("1621");

        expect(select.options).toHaveLength(1);

        const option = select.options[0];
        expect(option?.value).toBe("1621");
        expect(option?.innerHTML).toBe("story #1621 - Do some stuff");
    });

    it("when an error is returned by the api, then an empty state option is added to the source select", async () => {
        const fault = Fault.fromMessage("Nope");
        const autocompleter = ArtifactLinkSelectorAutoCompleter(
            RetrieveMatchingArtifactStub.withFault(fault),
            null
        );

        await autocompleter.autoComplete(select)("1621");

        expect(select.options).toHaveLength(1);

        const option = select.options[0];
        expect(option?.value).toBe("");
        expect(option?.hasAttribute("disabled")).toBe(true);
        expect(option?.dataset?.linkSelectorRole).toBe("empty-state");
        expect(option?.innerHTML).toBe("No result found");
    });
});
