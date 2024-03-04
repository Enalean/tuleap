/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { IterationsState } from "./type";
import type { Iteration } from "../../type";
import * as getters from "./iterations-getters";
import { DateTime } from "luxon";

describe("iterations-getters", () => {
    it.each([
        [1, 5, false],
        [10, 15, false],
        [10, 17, false],
        [15, 17, true],
        [16, 19, true],
        [16, 20, true],
        [16, 25, false],
        [20, 25, false],
        [25, 30, false],
    ])(
        "Knowing that first date is 15th and last date is 20th, when iteration start on %i and ends on %i, then display of iteration is %s",
        (start: number, end: number, expected_is_displayed: boolean): void => {
            const root_getters = {
                "timeperiod/first_date": DateTime.fromISO(`2020-04-15T22:00:00.000Z`),
                "timeperiod/last_date": DateTime.fromISO(`2020-04-20T22:00:00.000Z`),
            };

            const state: IterationsState = {
                lvl1_iterations: [
                    {
                        id: 123,
                        start: DateTime.fromISO(`2020-04-${start}T22:00:00.000Z`),
                        end: DateTime.fromISO(`2020-04-${end}T22:00:00.000Z`),
                    } as Iteration,
                ],
                lvl2_iterations: [
                    {
                        id: 124,
                        start: DateTime.fromISO(`2020-04-${start}T22:00:00.000Z`),
                        end: DateTime.fromISO(`2020-04-${end}T22:00:00.000Z`),
                    } as Iteration,
                ],
            };

            expect(
                getters
                    .lvl1_iterations_to_display(state, {}, {}, root_getters)
                    .some((iteration) => iteration.id === 123),
            ).toBe(expected_is_displayed);
            expect(
                getters
                    .lvl2_iterations_to_display(state, {}, {}, root_getters)
                    .some((iteration) => iteration.id === 124),
            ).toBe(expected_is_displayed);
        },
    );
});
