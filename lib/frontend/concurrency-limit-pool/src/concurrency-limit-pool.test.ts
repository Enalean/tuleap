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

import { describe, it, expect } from "vitest";
import { limitConcurrencyPool } from "./concurrency-limit-pool";

describe("concurrency-limit-pool", () => {
    it("runs all the promises in parallel as allowed by the pool limit and return values in order", async () => {
        const results: number[] = [];
        const wait_and_return_value = (i: number): Promise<number> =>
            new Promise((resolve) =>
                setTimeout(() => {
                    results.push(i);
                    resolve(i);
                }, i),
            );

        const wait_values = [50, 250, 150, 100];
        const resolved_results = await limitConcurrencyPool(2, wait_values, wait_and_return_value);
        expect(results).toStrictEqual([50, 150, 250, 100]);
        expect(resolved_results).toStrictEqual(wait_values);
    });

    it("needs a positive number as the limit pool value", async () => {
        await expect(limitConcurrencyPool(0, [], () => Promise.resolve())).rejects.toThrow();
    });
});
