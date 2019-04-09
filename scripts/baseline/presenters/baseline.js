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

import { getArtifact, getTracker, getUser } from "../api/rest-querier";
import ArrayUtils from "../support/array-utils";

export { presentBaseline, presentBaselines, presentArtifacts };

async function presentBaselines(baselines) {
    let users_loading = fetchUsers(baselines);
    let artifacts_loading = fetchArtifacts(baselines);

    const users = await users_loading;
    const artifacts = await artifacts_loading;
    const trackers = await fetchTrackers(artifacts);

    return baselines.map(baseline => {
        const author = ArrayUtils.find(users, user => user.id === baseline.author_id);
        const artifact = ArrayUtils.find(
            artifacts,
            artifact => artifact.id === baseline.artifact_id
        );
        artifact.tracker = ArrayUtils.find(trackers, tracker => tracker.id === artifact.tracker.id);

        return { ...baseline, author, artifact };
    });
}

function fetchUsers(baselines) {
    const user_ids = baselines.map(baseline => baseline.author_id);
    return fetchById(user_ids, getUser);
}

function fetchArtifacts(baselines) {
    const user_ids = baselines.map(baseline => baseline.artifact_id);
    return fetchById(user_ids, getArtifact);
}

function fetchTrackers(artifacts) {
    const simplified_tracker_ids = artifacts.map(artifact => artifact.tracker.id);
    return fetchById(simplified_tracker_ids, getTracker);
}

function fetchById(ids, fetcher) {
    const uniq_ids = ArrayUtils.unique(ids);
    return Promise.all(uniq_ids.map(id => fetcher(id)));
}

async function presentBaseline(baseline) {
    const user = await getUser(baseline.author_id);

    return { ...baseline, author: user };
}

function presentArtifacts(artifacts, baseline_id) {
    return artifacts.map(artifact => ({ ...artifact, baseline_id }));
}
