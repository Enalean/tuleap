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

import Vue from "vue";
import Vuex, { Store } from "vuex";
import mutations from "./mutations";
import * as getters from "./getters";
import * as actions from "./actions";
import { State, TrackerAgileDashboard } from "../type";

Vue.use(Vuex);

export function createStore(
    project_id: number,
    nb_upcoming_releases: number,
    nb_backlog_items: number,
    trackers_agile_dashboard: TrackerAgileDashboard[],
    is_browser_IE11: boolean,
    label_tracker_planning: string
): Store<State> {
    const state: State = {
        project_id,
        nb_upcoming_releases,
        nb_backlog_items,
        trackers_agile_dashboard,
        is_browser_IE11,
        error_message: null,
        offset: 0,
        limit: 50,
        is_loading: false,
        current_milestones: [],
        label_tracker_planning
    };

    return new Vuex.Store({
        getters,
        actions,
        state,
        mutations
    });
}
