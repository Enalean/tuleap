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

import type { ResultAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import { LinksRetriever } from "./LinksRetriever";
import { RetrieveLinkTypesStub } from "../../../../tests/stubs/RetrieveLinkTypesStub";
import { RetrieveLinkedArtifactsByTypeStub } from "../../../../tests/stubs/RetrieveLinkedArtifactsByTypeStub";
import type { LinkedArtifact } from "./LinkedArtifact";
import { CurrentArtifactIdentifierStub } from "../../../../tests/stubs/CurrentArtifactIdentifierStub";
import type { CurrentArtifactIdentifier } from "../../CurrentArtifactIdentifier";
import type { RetrieveLinkTypes } from "./RetrieveLinkTypes";
import type { RetrieveLinkedArtifactsByType } from "./RetrieveLinkedArtifactsByType";
import { AddLinkedArtifactCollectionStub } from "../../../../tests/stubs/AddLinkedArtifactCollectionStub";
import { LinkedArtifactStub } from "../../../../tests/stubs/LinkedArtifactStub";
import { LinkTypeStub } from "../../../../tests/stubs/LinkTypeStub";

const isCreationMode = (fault: Fault): boolean =>
    "isCreationMode" in fault && fault.isCreationMode() === true;

describe(`LinksRetriever`, () => {
    let first_child: LinkedArtifact,
        second_child: LinkedArtifact,
        first_parent: LinkedArtifact,
        second_parent: LinkedArtifact,
        current_artifact_option: Option<CurrentArtifactIdentifier>,
        types_retriever: RetrieveLinkTypes,
        linked_artifacts_retriever: RetrieveLinkedArtifactsByType,
        links_adder: AddLinkedArtifactCollectionStub;

    beforeEach(() => {
        current_artifact_option = Option.fromValue(CurrentArtifactIdentifierStub.withId(64));

        const child_type = LinkTypeStub.buildChildLinkType();
        const parent_type = LinkTypeStub.buildParentLinkType();
        types_retriever = RetrieveLinkTypesStub.withTypes(child_type, parent_type);

        first_child = LinkedArtifactStub.withDefaults({
            title: "A parent",
            link_type: parent_type,
        });
        second_child = LinkedArtifactStub.withDefaults({
            title: "Another parent",
            link_type: parent_type,
        });
        first_parent = LinkedArtifactStub.withDefaults({ title: "A child", link_type: child_type });
        second_parent = LinkedArtifactStub.withDefaults({
            title: "Another child",
            link_type: child_type,
        });
        linked_artifacts_retriever =
            RetrieveLinkedArtifactsByTypeStub.withSuccessiveLinkedArtifacts(
                [first_parent, second_parent],
                [first_child, second_child],
            );

        links_adder = AddLinkedArtifactCollectionStub.withCount();
    });

    const getLinkedArtifacts = (): ResultAsync<readonly LinkedArtifact[], Fault> => {
        const retriever = LinksRetriever(
            types_retriever,
            linked_artifacts_retriever,
            links_adder,
            current_artifact_option,
        );
        return retriever.getLinkedArtifacts();
    };

    it(`fetches all types of links from the given artifact id
        and will return all artifacts linked to it`, async () => {
        const result = await getLinkedArtifacts();

        if (!result.isOk()) {
            throw Error("Expected an Ok");
        }
        const artifacts = result.value;
        expect(artifacts).toHaveLength(4);
        expect(artifacts).toContain(first_parent);
        expect(artifacts).toContain(second_parent);
        expect(artifacts).toContain(first_child);
        expect(artifacts).toContain(second_child);
        expect(links_adder.getCallCount()).toBe(1);
    });

    it(`when the modal is in creation mode, it will return a Fault`, async () => {
        current_artifact_option = Option.nothing();
        const result = await getLinkedArtifacts();
        if (!result.isErr()) {
            throw Error("Expected an Err");
        }
        expect(isCreationMode(result.error)).toBe(true);
    });

    it(`when there is an error during retrieval of the types, it will return a Fault`, async () => {
        types_retriever = RetrieveLinkTypesStub.withFault(Fault.fromMessage("Network error"));
        const result = await getLinkedArtifacts();
        expect(result.isErr()).toBe(true);
    });

    it(`when there is an error during retrieval of the linked artifacts, it will return a Fault`, async () => {
        linked_artifacts_retriever = RetrieveLinkedArtifactsByTypeStub.withFault(
            Fault.fromMessage("Network error"),
        );
        const result = await getLinkedArtifacts();
        expect(result.isErr()).toBe(true);
    });
});
