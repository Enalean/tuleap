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

import { createStore } from "vuex";
import state_defaults from "./state-defaults";
import * as actions from "./actions";
import * as mutations from "./mutations";
import * as getters from "./getters";
import { createConfigurationModule } from "./configuration";
import type { Store } from "vuex";
import type { State } from "../type";
import type { ConfigurationState } from "./configuration";

export function createInitializedStore(configuration_state: ConfigurationState): Store<State> {
    const configuration = createConfigurationModule(configuration_state);

    return createStore({
        state: { ...state_defaults },
        actions,
        mutations,
        getters,
        modules: {
            configuration,
        },
    });
}
