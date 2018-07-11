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
import Vuex from "vuex";
import mutations from "./mutations.js";
import * as getters from "./getters.js";
import * as actions from "./actions.js";
import {
    REPOSITORIES_SORTED_BY_LAST_UPDATE,
    ERROR_TYPE_NO_ERROR,
    PROJECT_KEY
} from "../constants.js";

Vue.use(Vuex);

const state = {
    repositories_for_owner: {},
    filter: "",
    selected_owner_id: PROJECT_KEY,
    error_message_type: ERROR_TYPE_NO_ERROR,
    is_loading_initial: true,
    is_loading_next: true,
    add_repository_modal: null,
    display_mode: REPOSITORIES_SORTED_BY_LAST_UPDATE
};

export default new Vuex.Store({
    state,
    getters,
    mutations,
    actions
});
