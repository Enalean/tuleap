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

import * as actions from "./transition-actions.js";
import * as mutations from "./transition-mutations.js";
import * as getters from "./transition-getters.js";

export default {
    namespaced: true,
    state: {
        current_transition: null,
        is_loading_modal: false,
        is_modal_shown: false,
        is_modal_save_running: false,
        is_modal_operation_failed: false,
        modal_operation_failure_message: null,
        user_groups: null,
        post_actions_by_unique_id: null,
        new_post_action_unique_id_index: 0,
        used_services_names: [],
    },
    getters,
    mutations,
    actions,
};
