/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
import type { Fault } from "@tuleap/fault";
import { getResponse, uri } from "@tuleap/fetch-result";
import type { RetrieveCurrentArtifactWithTrackerStructure } from "../../../domain/initialization/RetrieveCurrentArtifactWithTrackerStructure";
import type { CurrentArtifactIdentifier } from "../../../domain/CurrentArtifactIdentifier";
import type { CurrentArtifactWithTrackerStructure } from "../../../domain/initialization/CurrentArtifactWithTrackerStructure";

export type InitializationAPIClient = RetrieveCurrentArtifactWithTrackerStructure;

export const InitializationAPIClient = (): InitializationAPIClient => ({
    getCurrentArtifactWithTrackerStructure(
        current_artifact: CurrentArtifactIdentifier,
    ): ResultAsync<CurrentArtifactWithTrackerStructure, Fault> {
        return getResponse(
            uri`/api/v1/artifacts/${current_artifact.id}?tracker_structure_format=complete`,
        ).map(async (response) => {
            const artifact_with_structure: CurrentArtifactWithTrackerStructure = {
                etag: response.headers.get("Etag"),
                last_modified: response.headers.get("Last-Modified"),
                ...(await response.json()),
            };
            return artifact_with_structure;
        });
    },
});
