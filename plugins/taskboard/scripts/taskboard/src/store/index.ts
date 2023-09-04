/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
import * as mutations from "./mutations";
import * as actions from "./actions";
import * as getters from "./getters";
import error from "./error";
import swimlane from "./swimlane";
import fullscreen from "./fullscreen";
import type { UserState } from "./user/type";
import { createUserModule } from "./user";
import type { RootState } from "./type";
import type { ColumnState } from "./column/type";
import { createColumnModule } from "./column";

export function createStore(
    initial_root_state: RootState,
    initial_user_state: UserState,
    initial_column_state: ColumnState,
): Store<RootState> {
    const user = createUserModule(initial_user_state);
    const column = createColumnModule(initial_column_state);

    const store_options: StoreOptions<RootState> = {
        state: initial_root_state,
        mutations,
        actions,
        getters,
        modules: {
            error,
            swimlane,
            user,
            fullscreen,
            column,
        },
    };

    return new Store(store_options);
}
