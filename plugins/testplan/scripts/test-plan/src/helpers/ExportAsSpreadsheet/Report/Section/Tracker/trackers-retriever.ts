/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { get } from "@tuleap/tlp-fetch";
import type { MinimalTracker, Tracker } from "./tracker";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";

const MAX_CONCURRENT_REQUESTS_WHEN_RETRIEVING_TRACKERS = 5;

export function retrieveTrackers(
    minimal_trackers: ReadonlyArray<MinimalTracker>,
): Promise<Tracker[]> {
    const deduplicated_tracker_id = new Set(
        minimal_trackers.map((minimal_tracker: MinimalTracker): number => minimal_tracker.id),
    );

    return limitConcurrencyPool(
        MAX_CONCURRENT_REQUESTS_WHEN_RETRIEVING_TRACKERS,
        [...deduplicated_tracker_id],
        retrieveTracker,
    );
}

async function retrieveTracker(tracker_id: number): Promise<Tracker> {
    const response = await get(`/api/v1/trackers/${encodeURIComponent(tracker_id)}`);
    return response.json();
}
