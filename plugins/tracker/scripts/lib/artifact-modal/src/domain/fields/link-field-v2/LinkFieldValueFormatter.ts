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

import type { RetrieveLinkedArtifactsSync } from "./RetrieveLinkedArtifactsSync";
import type { VerifyLinkIsMarkedForRemoval } from "./VerifyLinkIsMarkedForRemoval";
import type { LinkFieldValueFormat } from "./LinkFieldValueFormat";
import { FORWARD_DIRECTION } from "./LinkType";
import type { RetrieveNewLinks } from "./RetrieveNewLinks";
import { Link } from "./LinkFieldValueFormat";

interface FormatLinkFieldValue {
    getFormattedValuesByFieldId: (field_id: number) => LinkFieldValueFormat;
}

export const LinkFieldValueFormatter = (
    retrieve_linked_artifacts: RetrieveLinkedArtifactsSync,
    verify_link_is_marked_for_removal: VerifyLinkIsMarkedForRemoval,
    retrieve_new_links: RetrieveNewLinks
): FormatLinkFieldValue => {
    return {
        getFormattedValuesByFieldId: (field_id: number): LinkFieldValueFormat => {
            const links_not_removed = retrieve_linked_artifacts
                .getLinkedArtifacts()
                .filter(({ link_type }) => link_type.direction === FORWARD_DIRECTION)
                .filter(
                    (linked_artifact) =>
                        !verify_link_is_marked_for_removal.isMarkedForRemoval(linked_artifact)
                )
                .map(Link.fromLinkedArtifact);

            const new_links = retrieve_new_links
                .getNewLinks()
                .filter(({ link_type }) => link_type.direction === FORWARD_DIRECTION)
                .map(Link.fromNewLink);

            return {
                field_id,
                links: links_not_removed.concat(new_links),
            };
        },
    };
};
