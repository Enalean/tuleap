/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { getInternationalizedTestStatus } from "./internationalize-test-status";
import { createVueGettextProviderPassthrough } from "../../vue-gettext-provider-for-test";
import type { TestStats } from "../../BacklogItems/compute-test-stats";

describe("Internationalize test status", () => {
    it.each([
        ["passed", "Passed"],
        ["blocked", "Blocked"],
        ["failed", "Failed"],
        ["notrun", "Not run"],
        [null, ""],
    ])("internationalizes %p", (test_status: string | null, expected: string) => {
        expect(
            getInternationalizedTestStatus(
                createVueGettextProviderPassthrough(),
                test_status as keyof TestStats | null,
            ),
        ).toBe(expected);
    });
});
