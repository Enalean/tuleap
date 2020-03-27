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

import * as actions from "./swimlane-actions";
import * as mutations from "./swimlane-mutations";
import * as getters from "./swimlane-getters";
import { SwimlaneState } from "./type";
import { Module } from "vuex";
import { RootState } from "../type";
import { UserForPeoplePicker } from "./card/type";

const swimlane_module_default: Module<SwimlaneState, RootState> = {
    namespaced: true,
    actions,
    mutations,
    getters,
    state: {
        swimlanes: [],
        is_loading_swimlanes: false,
        is_card_creation_blocked_due_to_ongoing_creation: false,
        possible_assignees: new Map<number, UserForPeoplePicker[]>(),
    },
};
export default swimlane_module_default;
