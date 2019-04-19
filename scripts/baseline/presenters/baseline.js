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
 *
 */

import { getBaseline, getBaselineArtifacts, getUser } from "../api/rest-querier";
import { fetchAllArtifacts } from "../api/request-manufacturer";
import { presentLinkedArtifactsAsGraph } from "./baseline-artifact";

export { presentBaseline, presentBaselineWithArtifactsAsGraph };

async function presentBaselineWithArtifactsAsGraph(baseline_id) {
    const baseline_loading = getBaseline(baseline_id);
    const first_level_artifacts_loading = getBaselineArtifacts(baseline_id);

    const baseline = await baseline_loading;
    const first_level_artifacts = await first_level_artifacts_loading;

    const presented_baseline = await presentBaseline(baseline);
    const all_artifacts = await fetchAllArtifacts(baseline_id, first_level_artifacts);
    const first_level_artifacts_as_graph = presentLinkedArtifactsAsGraph(
        first_level_artifacts,
        all_artifacts
    );
    return { ...presented_baseline, first_level_artifacts: first_level_artifacts_as_graph };
}

async function presentBaseline(baseline) {
    const user = await getUser(baseline.author_id);

    return { ...baseline, author: user };
}
