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

import { getUser } from "../api/rest-querier";

export { presentBaseline, presentBaselines, presentArtifacts };

async function presentBaselines(baselines) {
    const user_ids = baselines.map(baseline => baseline.author_id);
    const uniq_user_ids = [...new Set(user_ids)];
    const users = await Promise.all(uniq_user_ids.map(user_id => getUser(user_id)));

    return baselines.map(baseline => {
        const matching_users = users.filter(user => user.id === baseline.author_id);
        return { ...baseline, author: matching_users[0] };
    });
}

async function presentBaseline(baseline) {
    const user = await getUser(baseline.author_id);
    return { ...baseline, author: user };
}

function presentArtifacts(artifacts, baseline_id) {
    return artifacts.map(artifact => ({ ...artifact, baseline_id }));
}
