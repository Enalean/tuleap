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

import { createStore } from "vuex";
import type { Store } from "vuex";
import * as getters from "./getters";
import * as mutations from "./mutations";
import type { State } from "../type";

export function createInitializedStore(report_id: number, is_user_admin: boolean): Store<State> {
    const state: State = {
        report_id,
        is_user_admin,
        reading_mode: true,
        is_report_saved: true,
        error_message: null,
        success_message: null,
        invalid_trackers: [],
    };
    return createStore({
        getters,
        mutations,
        state,
    });
}
