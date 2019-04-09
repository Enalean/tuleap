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
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import ArrayUtils from "../support/array-utils";
import { ARTIFACTS_EXPLORATION_DEPTH_LIMIT } from "../constants";

export { presentLinkedArtifactsAsGraph };

/**
 * @param current_depth Allows you to know the depth of the graph of the related artifacts, so you can limit its depth.
 */
function presentLinkedArtifactsAsGraph(artifacts, all_artifacts, current_depth = 1) {
    return artifacts.map(artifact => presentArtifact(artifact, all_artifacts, current_depth));
}

function presentArtifact(artifact, all_artifacts, current_depth) {
    if (current_depth >= ARTIFACTS_EXPLORATION_DEPTH_LIMIT) {
        artifact.linked_artifacts = [];
        artifact.is_depth_limit_reached = artifact.linked_artifact_ids.length > 0;
        return artifact;
    }

    const matched_linked_artifacts = findLinkedArtifacts(artifact, all_artifacts);
    const cloned_linked_artifacts = ArrayUtils.clone(matched_linked_artifacts);

    artifact.is_depth_limit_reached = false;
    artifact.linked_artifacts = presentLinkedArtifactsAsGraph(
        cloned_linked_artifacts,
        all_artifacts,
        current_depth + 1
    );

    return artifact;
}

function findLinkedArtifacts(artifact, all_artifacts) {
    return artifact.linked_artifact_ids.map(id =>
        ArrayUtils.find(all_artifacts, artifact => artifact.id === id)
    );
}
