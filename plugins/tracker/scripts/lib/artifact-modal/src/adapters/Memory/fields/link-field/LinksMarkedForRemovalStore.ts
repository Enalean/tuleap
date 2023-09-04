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

import type { AddLinkMarkedForRemoval } from "../../../../domain/fields/link-field/AddLinkMarkedForRemoval";
import type { DeleteLinkMarkedForRemoval } from "../../../../domain/fields/link-field/DeleteLinkMarkedForRemoval";
import type {
    LinkedArtifact,
    LinkedArtifactIdentifier,
} from "../../../../domain/fields/link-field/LinkedArtifact";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field/VerifyLinkIsMarkedForRemoval";

type LinksStoreType = AddLinkMarkedForRemoval &
    VerifyLinkIsMarkedForRemoval &
    DeleteLinkMarkedForRemoval;

export const LinksMarkedForRemovalStore = (): LinksStoreType => {
    let links_marked_for_removal: LinkedArtifactIdentifier[] = [];

    return {
        isMarkedForRemoval: (artifact: LinkedArtifact): boolean =>
            links_marked_for_removal.includes(artifact.identifier),

        addLinkMarkedForRemoval: (link_identifier: LinkedArtifactIdentifier): void => {
            links_marked_for_removal.push(link_identifier);
        },

        deleteLinkMarkedForRemoval: (link_identifier: LinkedArtifactIdentifier): void => {
            links_marked_for_removal = links_marked_for_removal.filter(
                (marked_identifier) => marked_identifier.id !== link_identifier.id,
            );
        },
    };
};
