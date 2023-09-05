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

import { LinkFieldValueFormatter } from "./LinkFieldValueFormatter";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field/VerifyLinkIsMarkedForRemoval";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import type { RetrieveLinkedArtifactsSync } from "../../../../domain/fields/link-field/RetrieveLinkedArtifactsSync";
import type { RetrieveNewLinks } from "../../../../domain/fields/link-field/RetrieveNewLinks";
import { IS_CHILD_LINK_TYPE, UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import type { ArtifactLinkNewChangesetValue } from "@tuleap/plugin-tracker-rest-api-types";
import {
    FORWARD_DIRECTION,
    REVERSE_DIRECTION,
} from "../../../../domain/fields/link-field/LinkType";

const FIELD_ID = 1060;
const FIRST_LINKED_ARTIFACT_ID = 666;
const SECOND_LINKED_ARTIFACT_ID = 667;
const THIRD_LINKED_ARTIFACT_ID = 668;
const FIRST_NEW_LINK_ID = 985;
const SECOND_NEW_LINK_ID = 111;

describe("LinkFieldValueFormatter", () => {
    let links_retriever: RetrieveLinkedArtifactsSync,
        new_links_retriever: RetrieveNewLinks,
        verifier: VerifyLinkIsMarkedForRemoval;

    beforeEach(() => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(
            LinkedArtifactStub.withIdAndType(
                FIRST_LINKED_ARTIFACT_ID,
                LinkTypeStub.buildParentLinkType(),
            ),
            LinkedArtifactStub.withIdAndType(
                SECOND_LINKED_ARTIFACT_ID,
                LinkTypeStub.buildParentLinkType(),
            ),
            LinkedArtifactStub.withIdAndType(THIRD_LINKED_ARTIFACT_ID, LinkTypeStub.buildUntyped()),
        );
        new_links_retriever = RetrieveNewLinksStub.withNewLinks(
            NewLinkStub.withIdAndType(FIRST_NEW_LINK_ID, LinkTypeStub.buildUntyped()),
            NewLinkStub.withIdAndType(SECOND_NEW_LINK_ID, LinkTypeStub.buildChildLinkType()),
        );

        verifier = VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval();
    });

    const format = (): ArtifactLinkNewChangesetValue => {
        const formatter = LinkFieldValueFormatter(links_retriever, verifier, new_links_retriever);
        return formatter.getFormattedValuesByFieldId(FIELD_ID);
    };

    it("should remove the links to be deleted from the list", () => {
        const artifact_link_to_delete = LinkedArtifactStub.withIdAndType(
            666,
            LinkTypeStub.buildParentLinkType(),
        );
        links_retriever =
            RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(artifact_link_to_delete);
        verifier = VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval();
        new_links_retriever = RetrieveNewLinksStub.withoutLink();

        const value = format();
        expect(value.all_links).toHaveLength(0);
    });

    it("formats the existing links and the new links into a single array", () => {
        expect(format()).toStrictEqual({
            field_id: FIELD_ID,
            all_links: [
                {
                    id: FIRST_LINKED_ARTIFACT_ID,
                    type: IS_CHILD_LINK_TYPE,
                    direction: FORWARD_DIRECTION,
                },
                {
                    id: SECOND_LINKED_ARTIFACT_ID,
                    type: IS_CHILD_LINK_TYPE,
                    direction: FORWARD_DIRECTION,
                },
                { id: THIRD_LINKED_ARTIFACT_ID, type: UNTYPED_LINK, direction: FORWARD_DIRECTION },
                { id: FIRST_NEW_LINK_ID, type: UNTYPED_LINK, direction: FORWARD_DIRECTION },
                { id: SECOND_NEW_LINK_ID, type: IS_CHILD_LINK_TYPE, direction: REVERSE_DIRECTION },
            ],
        });
    });

    it(`adds only new links when there are no existing links`, () => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withoutLink();

        expect(format()).toStrictEqual({
            field_id: FIELD_ID,
            all_links: [
                { id: FIRST_NEW_LINK_ID, type: UNTYPED_LINK, direction: FORWARD_DIRECTION },
                { id: SECOND_NEW_LINK_ID, type: IS_CHILD_LINK_TYPE, direction: REVERSE_DIRECTION },
            ],
        });
    });

    it(`adds only existing links when there are no new links`, () => {
        new_links_retriever = RetrieveNewLinksStub.withoutLink();

        expect(format()).toStrictEqual({
            field_id: FIELD_ID,
            all_links: [
                {
                    id: FIRST_LINKED_ARTIFACT_ID,
                    type: IS_CHILD_LINK_TYPE,
                    direction: FORWARD_DIRECTION,
                },
                {
                    id: SECOND_LINKED_ARTIFACT_ID,
                    type: IS_CHILD_LINK_TYPE,
                    direction: FORWARD_DIRECTION,
                },
                { id: THIRD_LINKED_ARTIFACT_ID, type: UNTYPED_LINK, direction: FORWARD_DIRECTION },
            ],
        });
    });

    it(`returns an empty array when there are neither existing links nor new links`, () => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withoutLink();
        new_links_retriever = RetrieveNewLinksStub.withoutLink();

        const value = format();
        expect(value.all_links).toHaveLength(0);
    });
});
