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

import Vue from "vue";
import Vuex from "vuex";
import createPersistedState from "vuex-persistedstate";
import createMutationsSharer from "vuex-shared-mutations";
import { expiringLocalStorage } from "./store-persistence/storage.js";
import * as mutations from "./mutations.js";
import * as getters from "./getters.js";
import * as actions from "./actions.js";
import state from "./state.js";
import error from "./error/module.js";
import clipboard from "./clipboard/module.js";
import metadata from "./metadata/module.js";

Vue.use(Vuex);

export function createStore(user_id, project_id) {
    return new Vuex.Store({
        state,
        getters,
        mutations,
        actions,
        modules: {
            error,
            clipboard,
            metadata,
        },
        plugins: [
            createPersistedState({
                key: `document_clipboard_${user_id}_${project_id}`,
                storage: expiringLocalStorage(900),
                paths: ["clipboard"],
            }),
            createMutationsSharer({
                predicate: (mutation) => {
                    return mutation.type.startsWith("clipboard/");
                },
            }),
        ],
    });
}
