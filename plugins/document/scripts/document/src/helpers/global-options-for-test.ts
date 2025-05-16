/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import type { StoreOptions } from "vuex";
import { createStore } from "vuex";
import type { RootState } from "../type";
import type { Pinia } from "pinia";
import { createTestingPinia } from "@pinia/testing";

export function getGlobalTestOptions(
    store_options: StoreOptions<RootState>,
    pinia?: Pinia,
): MountingOptions<unknown>["global"] {
    return {
        plugins: [
            createGettext({ silent: true }),
            createStore(store_options),
            pinia || createTestingPinia(),
        ],
    };
}
