/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { RootState } from "../type";
import type { TasksState } from "./type";
import type { Module } from "vuex";
import * as mutations from "./tasks-mutations";
import * as actions from "./tasks-actions";

export function createTaskModule(): Module<TasksState, RootState> {
    return {
        namespaced: true,
        state: {
            tasks: [],
            is_loading: true,
            should_display_empty_state: false,
            should_display_error_state: false,
            error_message: "",
        },
        mutations,
        actions,
    };
}
