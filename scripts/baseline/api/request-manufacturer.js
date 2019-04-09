/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
import { ARTIFACTS_EXPLORATION_DEPTH_LIMIT } from "../constants";
import { getBaselineArtifactsByIds } from "./rest-querier";
import ArrayUtils from "../support/array-utils";

async function fetchAllArtifacts(baseline_id, first_level_artifacts) {
    const all_artifacts = [...first_level_artifacts];
    let current_depth = 1;
    let current_depth_artifacts = first_level_artifacts;
    while (
        current_depth_artifacts.length > 0 &&
        current_depth <= ARTIFACTS_EXPLORATION_DEPTH_LIMIT
    ) {
        const all_linked_artifact_ids = current_depth_artifacts
            .map(artifact => artifact.linked_artifact_ids)
            .flat();
        if (all_linked_artifact_ids.length === 0) {
            break;
        }
        current_depth_artifacts = await getBaselineArtifactsByIds(
            baseline_id,
            ArrayUtils.unique(all_linked_artifact_ids)
        );

        all_artifacts.push(...current_depth_artifacts);
        current_depth++;
    }

    return ArrayUtils.unique(all_artifacts);
}

export { fetchAllArtifacts };
