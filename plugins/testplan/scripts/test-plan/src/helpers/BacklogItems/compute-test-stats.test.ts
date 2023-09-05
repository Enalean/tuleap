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

import type { TestStats } from "./compute-test-stats";
import { computeTestStats, getTestStatusFromStats } from "./compute-test-stats";
import type { BacklogItem } from "../../type";

describe("Compute test stats", () => {
    it("computes raw statistics", () => {
        const stats = computeTestStats({
            test_definitions: [
                { test_status: null },
                { test_status: "passed" },
                { test_status: "blocked" },
                { test_status: "notrun" },
                { test_status: "passed" },
            ],
        } as BacklogItem);

        expect(stats).toStrictEqual({
            passed: 2,
            failed: 0,
            blocked: 1,
            notrun: 1,
        });
    });

    it.each([
        [{ passed: 1, failed: 1, blocked: 1, notrun: 1 }, "failed"],
        [{ passed: 1, failed: 0, blocked: 1, notrun: 1 }, "blocked"],
        [{ passed: 1, failed: 0, blocked: 0, notrun: 1 }, "notrun"],
        [{ passed: 1, failed: 0, blocked: 0, notrun: 0 }, "passed"],
        [{ passed: 0, failed: 0, blocked: 0, notrun: 0 }, null],
    ])(
        "determines global status from statistics",
        (stats: TestStats, expected_status: string | null) => {
            expect(getTestStatusFromStats(stats)).toBe(expected_status);
        },
    );
});
