/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { Store } from "vuex-mock-store";

/**
 * Create a Vuex Store with all actions, mutations and getters mocked.
 * Modules are handled with actions, mutations and getters also mocked.
 *
 * @param store_options
 * @param custom_state
 * @returns Store
 */

export function createStoreMock(store_options, custom_state = {}) {
    const state = Object.assign({}, store_options.state, custom_state);
    const options = Object.assign({}, store_options, {
        state,
    });

    return new Store(options);
}
