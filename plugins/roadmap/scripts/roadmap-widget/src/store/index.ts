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

import type { StoreOptions } from "vuex";
import { Store } from "vuex";
import type { RootState } from "./type";
import { createTaskModule } from "./tasks";
import { createIterationsModule } from "./iterations";
import { createTimeperiodModule } from "./timeperiod";
import * as actions from "./root-actions";
import * as mutations from "./root-mutations";
import type { TimeScale } from "../type";
import { DateTime } from "luxon";

export function createStore(
    initial_root_state: RootState,
    default_timescale: TimeScale,
): Store<RootState> {
    const state: RootState = {
        ...initial_root_state,
        is_loading: true,
        should_display_empty_state: false,
        should_display_error_state: false,
        error_message: "",
        now: DateTime.now(),
        show_closed_elements: false,
    };

    const store_options: StoreOptions<RootState> = {
        state,
        actions,
        mutations,
        modules: {
            tasks: createTaskModule(),
            iterations: createIterationsModule(),
            timeperiod: createTimeperiodModule(default_timescale),
        },
    };

    return new Store(store_options);
}
