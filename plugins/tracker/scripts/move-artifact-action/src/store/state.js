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

export default {
    is_loading_initial: true,
    are_trackers_loading: false,
    projects: [],
    trackers: [],
    error_message: "",
    selected_tracker: {
        tracker_id: null,
    },
    selected_project_id: null,
    dry_run_fields: {
        fields_not_migrated: [],
        fields_partially_migrated: [],
        fields_migrated: [],
    },
    has_processed_dry_run: false,
    is_processing_move: false,
};
