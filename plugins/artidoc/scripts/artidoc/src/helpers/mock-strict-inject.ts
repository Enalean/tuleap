/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { vi } from "vitest";
import * as strict_inject from "@tuleap/vue-strict-inject";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";

export function mockStrictInject(keys: [StrictInjectionKey<unknown>, unknown][]): void {
    const map = new Map(keys);

    vi.spyOn(strict_inject, "strictInject").mockImplementation(
        (key: StrictInjectionKey<unknown>): unknown => {
            const value = map.get(key);
            if (value === undefined) {
                throw new Error("Unknown key " + String(key));
            }

            return value;
        },
    );
}
