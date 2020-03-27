/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { MilestoneData, State } from "../type";

export default {
    setIsLoading(state: State, loading: boolean): void {
        state.is_loading = loading;
    },

    setNbPastReleases(state: State, total: number): void {
        state.nb_past_releases = total;
    },

    setErrorMessage(state: State, error_message: string): void {
        state.error_message = error_message;
    },

    resetErrorMessage(state: State): void {
        state.error_message = null;
    },

    setCurrentMilestones(state: State, milestones: MilestoneData[]): void {
        state.current_milestones = milestones;
    },

    setLastRelease(state: State, milestone: MilestoneData): void {
        state.last_release = milestone;
    },
};
