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

import type { ArtifactWithStatus } from "../../ArtifactWithStatus";
import type { LinkedArtifact } from "../../../../domain/fields/link-field/LinkedArtifact";
import { LinkedArtifactIdentifierProxy } from "./LinkedArtifactIdentifierProxy";
import { ArtifactCrossReferenceProxy } from "../../ArtifactCrossReferenceProxy";
import type { LinkType } from "../../../../domain/fields/link-field/LinkType";
import { ProjectProxy } from "../../ProjectProxy";

export const LinkedArtifactProxy = {
    fromAPIArtifactAndType: (
        artifact: ArtifactWithStatus,
        link_type: LinkType,
    ): LinkedArtifact => ({
        identifier: LinkedArtifactIdentifierProxy.fromAPIArtifact(artifact),
        title: artifact.title,
        status: artifact.full_status,
        xref: ArtifactCrossReferenceProxy.fromAPIArtifact(artifact),
        uri: artifact.html_url,
        is_open: artifact.is_open,
        project: ProjectProxy.fromAPIArtifact(artifact),
        link_type,
    }),
};
