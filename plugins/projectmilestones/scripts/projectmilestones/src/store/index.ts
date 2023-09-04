/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
import type { Store } from "vuex";
import Vuex from "vuex";
import mutations from "./mutations";
import * as getters from "./getters";
import * as actions from "./actions";
import type { BurnupMode, State, TrackerAgileDashboard } from "../type";

Vue.use(Vuex);

export function createStore(
    project_id: number,
    project_name: string,
    nb_upcoming_releases: number,
    nb_backlog_items: number,
    trackers_agile_dashboard: TrackerAgileDashboard[],
    label_tracker_planning: string,
    is_timeframe_duration: boolean,
    label_start_date: string,
    label_timeframe: string,
    user_can_view_sub_milestones_planning: boolean,
    burnup_mode: BurnupMode,
): Store<State> {
    const state: State = {
        project_id,
        project_name,
        nb_upcoming_releases,
        nb_backlog_items,
        trackers_agile_dashboard,
        error_message: null,
        offset: 0,
        limit: 50,
        is_loading: false,
        current_milestones: [],
        label_tracker_planning,
        is_timeframe_duration,
        label_start_date,
        label_timeframe,
        user_can_view_sub_milestones_planning,
        burnup_mode,
        nb_past_releases: 0,
        last_release: null,
    };

    return new Vuex.Store({
        getters,
        actions,
        state,
        mutations,
    });
}
