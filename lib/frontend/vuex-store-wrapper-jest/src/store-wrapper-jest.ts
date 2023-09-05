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
import type { StoreConstructorOptions, Dict } from "vuex-mock-store";
export type { Store } from "vuex-mock-store";

export function createStoreMock<S extends Dict, G extends Dict, Spy>(
    store_options: StoreConstructorOptions<S, G, Spy>,
    custom_state: S | Record<string, never> = {},
): Store<S, G, Spy> {
    const state = Object.assign({}, store_options.state, custom_state);
    const options = Object.assign({}, store_options, {
        state,
    });

    return new Store(options);
}
