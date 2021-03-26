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

import { getDimensions } from "./tasks-dimensions";
import { TimePeriodMonth } from "./time-period-month";
import type { Task } from "../type";
import { Styles } from "./styles";

describe("getDimensions", () => {
    it("Returns milestone dimensions, not positioned, for task without start nor end", () => {
        const task = { start: null, end: null } as Task;
        const time_period = new TimePeriodMonth(
            new Date(2020, 3, 10),
            new Date(2020, 3, 20),
            new Date(2020, 3, 15),
            "en_US"
        );

        expect(getDimensions(task, time_period)).toStrictEqual({
            left: 0,
            width: Styles.MILESTONE_WIDTH_IN_PX,
        });
    });

    it("Returns milestone dimensions, positioned at start date", () => {
        const task = { start: new Date(2020, 3, 20), end: null } as Task;
        const time_period = new TimePeriodMonth(
            new Date(2020, 3, 10),
            new Date(2020, 3, 20),
            new Date(2020, 3, 15),
            "en_US"
        );

        expect(getDimensions(task, time_period)).toStrictEqual({
            left: 63,
            width: Styles.MILESTONE_WIDTH_IN_PX,
        });
    });

    it("Returns milestone dimensions, positioned at end date", () => {
        const task = { start: null, end: new Date(2020, 3, 20) } as Task;
        const time_period = new TimePeriodMonth(
            new Date(2020, 3, 10),
            new Date(2020, 3, 20),
            new Date(2020, 3, 15),
            "en_US"
        );

        expect(getDimensions(task, time_period)).toStrictEqual({
            left: 63,
            width: Styles.MILESTONE_WIDTH_IN_PX,
        });
    });

    it("Returns milestone dimensions, positioned at start date, when start == end", () => {
        const task = { start: new Date(2020, 3, 20), end: new Date(2020, 3, 20) } as Task;
        const time_period = new TimePeriodMonth(
            new Date(2020, 3, 10),
            new Date(2020, 3, 20),
            new Date(2020, 3, 15),
            "en_US"
        );

        expect(getDimensions(task, time_period)).toStrictEqual({
            left: 63,
            width: Styles.MILESTONE_WIDTH_IN_PX,
        });
    });

    it("Returns task dimensions, positioned at start date", () => {
        const task = { start: new Date(2020, 3, 10), end: new Date(2020, 3, 20) } as Task;
        const time_period = new TimePeriodMonth(
            new Date(2020, 3, 10),
            new Date(2020, 3, 20),
            new Date(2020, 3, 15),
            "en_US"
        );

        expect(getDimensions(task, time_period)).toStrictEqual({
            left: 30,
            width: 33,
        });
    });

    it("Enusures that task width has a minimum width", () => {
        const task = { start: new Date(2020, 3, 10), end: new Date(2020, 3, 11) } as Task;
        const time_period = new TimePeriodMonth(
            new Date(2020, 3, 10),
            new Date(2020, 3, 20),
            new Date(2020, 3, 15),
            "en_US"
        );

        expect(getDimensions(task, time_period)).toStrictEqual({
            left: 30,
            width: Styles.TASK_BAR_MIN_WIDTH_IN_PX,
        });
    });
});
