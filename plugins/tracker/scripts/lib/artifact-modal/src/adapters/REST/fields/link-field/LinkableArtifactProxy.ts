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

import type { UserHistoryEntry } from "@tuleap/core-rest-api-types";
import { ArtifactCrossReferenceProxy } from "../../ArtifactCrossReferenceProxy";
import { ProjectProxy } from "../../ProjectProxy";
import type { ArtifactWithStatus } from "../../ArtifactWithStatus";
import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";

export const LinkableArtifactProxy = {
    fromAPIArtifact: (artifact: ArtifactWithStatus): LinkableArtifact => ({
        id: artifact.id,
        title: artifact.title,
        xref: ArtifactCrossReferenceProxy.fromAPIArtifact(artifact),
        uri: artifact.html_url,
        status: artifact.full_status,
        is_open: artifact.is_open,
        project: ProjectProxy.fromAPIArtifact(artifact),
    }),

    fromAPIUserHistory: (entry: UserHistoryEntry): LinkableArtifact => ({
        id: entry.per_type_id,
        title: entry.title,
        xref: ArtifactCrossReferenceProxy.fromAPIUserHistory(entry),
        uri: entry.html_url,
        status: entry.badges[0]
            ? { value: entry.badges[0].label, color: entry.badges[0].color }
            : null,
        is_open: true,
        project: entry.project,
    }),
};
