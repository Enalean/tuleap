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

export default {
    setProjectId(state, project_id) {
        state.project_id = project_id;
    },

    setIsLoading(state, loading) {
        state.is_loading = loading;
    },

    setNbBacklogItem(state, total) {
        state.nb_backlog_items = total;
    },

    setNbUpcomingReleases(state, total) {
        state.nb_upcoming_releases = total;
    },

    setErrorMessage(state, error_message) {
        state.error_message = error_message;
    },

    resetErrorMessage(state) {
        state.error_message = null;
    }
};
