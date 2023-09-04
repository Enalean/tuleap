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

import type { AddLinkedArtifactCollection } from "../../../../domain/fields/link-field/AddLinkedArtifactCollection";
import type { RetrieveLinkedArtifactsSync } from "../../../../domain/fields/link-field/RetrieveLinkedArtifactsSync";
import { LinkedArtifact } from "../../../../domain/fields/link-field/LinkedArtifact";
import type { ChangeLinkType } from "../../../../domain/fields/link-field/ChangeLinkType";

type LinksStoreType = AddLinkedArtifactCollection & RetrieveLinkedArtifactsSync & ChangeLinkType;

export const LinksStore = (): LinksStoreType => {
    let links: LinkedArtifact[] = [];

    return {
        getLinkedArtifacts(): LinkedArtifact[] {
            return links;
        },

        addLinkedArtifacts(new_links: LinkedArtifact[]): void {
            links = new_links;
        },

        changeLinkType(link, type): void {
            const updated_link = LinkedArtifact.fromLinkAndType(link, type);
            const index = links.findIndex(
                (stored_link) => stored_link.identifier.id === updated_link.identifier.id,
            );
            if (index === -1) {
                return;
            }
            links.splice(index, 1, updated_link);
        },
    };
};
