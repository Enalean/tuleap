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

import { LinksRetriever } from "./LinksRetriever";
import { RetrieveLinkTypesStub } from "../../../../tests/stubs/RetrieveLinkTypesStub";
import { RetrieveLinkedArtifactsByTypeStub } from "../../../../tests/stubs/RetrieveLinkedArtifactsByTypeStub";
import type { LinkedArtifact } from "./LinkedArtifact";
import { CurrentArtifactIdentifierStub } from "../../../../tests/stubs/CurrentArtifactIdentifierStub";
import type { CurrentArtifactIdentifier } from "../../CurrentArtifactIdentifier";
import type { Fault } from "@tuleap/fault";
import { isFault } from "@tuleap/fault";
import type { RetrieveLinkTypes } from "./RetrieveLinkTypes";
import type { RetrieveLinkedArtifactsByType } from "./RetrieveLinkedArtifactsByType";
import { AddLinkedArtifactCollectionStub } from "../../../../tests/stubs/AddLinkedArtifactCollectionStub";
import type { LinkType } from "./LinkType";

describe(`LinksRetriever`, () => {
    let parent_type: LinkType,
        child_type: LinkType,
        first_parent: LinkedArtifact,
        second_parent: LinkedArtifact,
        first_child: LinkedArtifact,
        second_child: LinkedArtifact,
        artifact_identifier: CurrentArtifactIdentifier | null,
        types_retriever: RetrieveLinkTypes,
        linked_artifacts_retriever: RetrieveLinkedArtifactsByType,
        links_adder: AddLinkedArtifactCollectionStub;

    beforeEach(() => {
        artifact_identifier = CurrentArtifactIdentifierStub.withId(64);

        parent_type = {
            shortname: "_is_child",
            direction: "forward",
            label: "Parent",
        };
        child_type = {
            shortname: "_is_child",
            direction: "reverse",
            label: "Child",
        };
        types_retriever = RetrieveLinkTypesStub.withTypes(parent_type, child_type);

        first_parent = {
            title: "A parent",
            link_type: child_type,
        } as LinkedArtifact;
        second_parent = {
            title: "Another parent",
            link_type: child_type,
        } as LinkedArtifact;
        first_child = {
            title: "A child",
            link_type: parent_type,
        } as LinkedArtifact;
        second_child = {
            title: "Another child",
            link_type: parent_type,
        } as LinkedArtifact;
        linked_artifacts_retriever =
            RetrieveLinkedArtifactsByTypeStub.withSuccessiveLinkedArtifacts(
                [first_child, second_child],
                [first_parent, second_parent]
            );

        links_adder = AddLinkedArtifactCollectionStub.withCount();
    });

    const getLinkedArtifacts = (): Promise<Fault | LinkedArtifact[]> => {
        const retriever = LinksRetriever(types_retriever, linked_artifacts_retriever, links_adder);
        return retriever.getLinkedArtifacts(artifact_identifier);
    };

    it(`fetches all types of links from the given artifact id
        and will return all artifacts linked to it`, async () => {
        const artifacts = await getLinkedArtifacts();
        if (isFault(artifacts)) {
            throw new Error("Expected to get a list of linked artifacts");
        }
        expect(artifacts).toHaveLength(4);
        expect(artifacts).toContain(first_child);
        expect(artifacts).toContain(second_child);
        expect(artifacts).toContain(first_parent);
        expect(artifacts).toContain(second_parent);
        expect(links_adder.getCallCount()).toBe(1);
    });

    it(`when the modal is in creation mode, it will return a Fault`, async () => {
        artifact_identifier = null;
        const result = await getLinkedArtifacts();
        expect(isFault(result)).toBe(true);
    });

    it(`when there is an error during retrieval of the types, it will return a Fault`, async () => {
        types_retriever = RetrieveLinkTypesStub.withError("Network error");
        const result = await getLinkedArtifacts();
        expect(isFault(result)).toBe(true);
    });

    it(`when there is an error during retrieval of the linked artifacts, it will return a Fault`, async () => {
        linked_artifacts_retriever = RetrieveLinkedArtifactsByTypeStub.withError("Network error");
        const result = await getLinkedArtifacts();
        expect(isFault(result)).toBe(true);
    });
});
