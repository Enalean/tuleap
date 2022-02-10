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
import { RetrieveLinkedArtifactsSyncStub } from "../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { LinkedArtifactStub } from "../../../../tests/stubs/LinkedArtifactStub";
import { LinkedArtifactIdentifierStub } from "../../../../tests/stubs/LinkedArtifactIdentifierStub";
import { LinkTypeStub } from "../../../../tests/stubs/LinkTypeStub";
import type { LinkedArtifact } from "./LinkedArtifact";

const field_id = 1060;

function createChildLinkedArtifact(artifact_id: number): LinkedArtifact {
    return LinkedArtifactStub.withLinkType(LinkTypeStub.buildChildLinkType(), {
        identifier: LinkedArtifactIdentifierStub.withId(artifact_id),
    });
}

describe("LinkFieldValueFormatter", () => {
    it("should remove the links to be deleted from the list", () => {
        const artifact_link_to_delete = createChildLinkedArtifact(666);
        const formatter = LinkFieldValueFormatter(
            RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(artifact_link_to_delete),
            VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval()
        );
        const formatted_value = formatter.getFormattedValuesByFieldId(field_id);

        expect(formatted_value).toEqual({
            field_id,
            links: [],
        });
    });

    it("should format the field value", () => {
        const artifact_link_1 = createChildLinkedArtifact(666);
        const artifact_link_2 = createChildLinkedArtifact(667);
        const artifact_link_3 = createChildLinkedArtifact(668);
        const formatter = LinkFieldValueFormatter(
            RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(
                artifact_link_1,
                artifact_link_2,
                artifact_link_3
            ),
            VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval()
        );

        const formatted_value = formatter.getFormattedValuesByFieldId(field_id);

        expect(formatted_value).toEqual({
            field_id,
            links: [
                {
                    id: 666,
                    type: "_is_child",
                },
                {
                    id: 667,
                    type: "_is_child",
                },
                {
                    id: 668,
                    type: "_is_child",
                },
            ],
        });
    });
});
