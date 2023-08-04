/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { MountingOptions } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { createStore } from "vuex";
import type { StoreOptions } from "vuex";
import type { RootState } from "../src/store/types";

export function getGlobalTestOptions(state?: RootState): MountingOptions<unknown>["global"] {
    return {
        plugins: [createGettext({ silent: true }), createStore({ state })],
    };
}

/**
 * Allows us to pass a StoreOptions object containing mocks for actions and mutations.
 *
 * Since we now use Vitest instead of jest, we cannot use @tuleap/vuex-store-wrapper-jest anymore.
 *
 * Will be removed during the replacement of Vuex with Pinia in the next migration step.
 */
export function getGlobalTestOptionsWithMockedStore(
    store: StoreOptions<RootState> = {}
): MountingOptions<unknown>["global"] {
    return {
        plugins: [createGettext({ silent: true }), createStore(store)],
    };
}
