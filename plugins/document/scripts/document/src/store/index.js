/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import * as mutations from "./mutations.js";
import * as getters from "./getters";
import * as actions from "./actions";
import state from "./state.js";
import error from "./error/module";
import properties from "./properties/module";
import lock from "./lock/module";
import preferencies from "./preferencies/module";
import permissions from "./permissions/module";
import { createConfigurationModule } from "./configuration";

export let store;

export function createInitializedStore(user_id, project_id, configuration_state) {
    const configuration = createConfigurationModule(configuration_state);

    return createStore({
        state,
        getters,
        mutations,
        actions,
        modules: {
            error,
            properties,
            lock,
            preferencies,
            permissions,
            configuration,
        },
    });
}
