/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import { getTracker } from "../api/rest-querier.js";

export async function loadTracker(context, tracker_id) {
    try {
        context.commit("startCurrentTrackerLoading", tracker_id);
        const tracker = await getTracker(tracker_id);
        context.commit("saveCurrentTracker", tracker);
    } catch (e) {
        context.commit("failCurrentTrackerLoading");
    } finally {
        context.commit("stopCurrentTrackerLoading");
    }
}
