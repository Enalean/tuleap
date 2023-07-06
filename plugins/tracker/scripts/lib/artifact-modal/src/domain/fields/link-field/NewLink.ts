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

import type { LinkedArtifact } from "./LinkedArtifact";
import type { LinkableArtifact } from "./LinkableArtifact";
import type { LinkType } from "./LinkType";
import { NewArtifactLinkIdentifier } from "./NewArtifactLinkIdentifier";

/**
 * Alias LinkedArtifact because they both need the same properties
 */
export type NewLink = Omit<LinkedArtifact, "identifier"> & {
    readonly identifier: NewArtifactLinkIdentifier;
};

export const NewLink = {
    fromLinkableArtifactAndType: (artifact: LinkableArtifact, type: LinkType): NewLink => ({
        identifier: NewArtifactLinkIdentifier.fromLinkableArtifact(artifact),
        title: artifact.title,
        xref: artifact.xref,
        uri: artifact.uri,
        status: artifact.status,
        is_open: artifact.is_open,
        project: artifact.project,
        link_type: type,
    }),

    fromNewLinkAndType: (link: NewLink, new_type: LinkType): NewLink => ({
        ...link,
        link_type: new_type,
    }),
};
