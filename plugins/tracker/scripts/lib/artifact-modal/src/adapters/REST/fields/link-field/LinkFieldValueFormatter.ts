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

import type { RetrieveLinkedArtifactsSync } from "../../../../domain/fields/link-field/RetrieveLinkedArtifactsSync";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field/VerifyLinkIsMarkedForRemoval";
import type { RetrieveNewLinks } from "../../../../domain/fields/link-field/RetrieveNewLinks";
import { NewChangesetLinkProxy } from "./NewChangesetLinkProxy";
import type { ArtifactLinkNewChangesetValue } from "@tuleap/plugin-tracker-rest-api-types";

interface FormatLinkFieldValue {
    getFormattedValuesByFieldId: (field_id: number) => ArtifactLinkNewChangesetValue;
}

export const LinkFieldValueFormatter = (
    retrieve_linked_artifacts: RetrieveLinkedArtifactsSync,
    verify_link_is_marked_for_removal: VerifyLinkIsMarkedForRemoval,
    retrieve_new_links: RetrieveNewLinks,
): FormatLinkFieldValue => ({
    getFormattedValuesByFieldId: (field_id: number): ArtifactLinkNewChangesetValue => {
        const links_not_removed = retrieve_linked_artifacts
            .getLinkedArtifacts()
            .filter(
                (linked_artifact) =>
                    !verify_link_is_marked_for_removal.isMarkedForRemoval(linked_artifact),
            )
            .map(NewChangesetLinkProxy.fromLinkedArtifact);

        const new_links = retrieve_new_links.getNewLinks();

        const new_forward_links = new_links.map(NewChangesetLinkProxy.fromNewLink);

        const all_links = links_not_removed.concat(new_forward_links);

        return { field_id, all_links };
    },
});
