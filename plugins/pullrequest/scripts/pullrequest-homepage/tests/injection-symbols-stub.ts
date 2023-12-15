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

import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import { BASE_URL, REPOSITORY_ID } from "../src/injection-symbols";

export const injected_repository_id = 2;
export const injected_base_url = new URL("https://example.com");

export const injection_symbols_stub = (key: StrictInjectionKey<unknown>): unknown => {
    switch (key) {
        case REPOSITORY_ID:
            return injected_repository_id;
        case BASE_URL:
            return injected_base_url;
        default:
            throw new Error("Tried to strictInject a value while it was not mocked");
    }
};
