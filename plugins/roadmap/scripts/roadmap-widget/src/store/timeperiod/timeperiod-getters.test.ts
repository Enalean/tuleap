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

import type { TimeperiodState } from "./type";
import * as getters from "./timeperiod-getters";
import type { RootState } from "../type";
import type { VueGettextProvider } from "../../helpers/vue-gettext-provider";
import { TimePeriodWeek } from "../../helpers/time-period-week";
import { TimePeriodMonth } from "../../helpers/time-period-month";
import { TimePeriodQuarter } from "../../helpers/time-period-quarter";
import type { Iteration, Task } from "../../type";

describe("timeperiod-getters", () => {
    describe("first_date", () => {
        it("should return the first task date if no iteration starts before", () => {
            const first_date = getters.first_date(
                {},
                {},
                {
                    iterations: {
                        lvl1_iterations: [
                            {
                                start: new Date(2020, 3, 15),
                                end: new Date(2020, 4, 15),
                            } as Iteration,
                            {
                                start: new Date(2020, 5, 15),
                                end: new Date(2020, 6, 15),
                            } as Iteration,
                        ],
                        lvl2_iterations: [
                            {
                                start: new Date(2020, 4, 15),
                                end: new Date(2020, 5, 15),
                            } as Iteration,
                            {
                                start: new Date(2020, 6, 15),
                                end: new Date(2020, 7, 15),
                            } as Iteration,
                        ],
                    },
                    now: new Date(2020, 3, 20),
                } as RootState,
                {
                    "tasks/tasks": [
                        { start: new Date(2020, 3, 15) } as Task,
                        { start: new Date(2020, 4, 15) } as Task,
                    ],
                },
            );

            expect(first_date.getMonth()).toBe(3);
        });

        it("should return the start date of the older iteration having dates around first task date", () => {
            const first_date = getters.first_date(
                {},
                {},
                {
                    iterations: {
                        lvl1_iterations: [
                            {
                                start: new Date(2020, 1, 15),
                                end: new Date(2020, 4, 15),
                            } as Iteration,
                            {
                                start: new Date(2020, 5, 15),
                                end: new Date(2020, 6, 15),
                            } as Iteration,
                        ],
                        lvl2_iterations: [
                            {
                                start: new Date(2020, 2, 15),
                                end: new Date(2020, 5, 15),
                            } as Iteration,
                            {
                                start: new Date(2020, 6, 15),
                                end: new Date(2020, 7, 15),
                            } as Iteration,
                        ],
                    },
                    now: new Date(2020, 3, 20),
                } as RootState,
                {
                    "tasks/tasks": [
                        { start: new Date(2020, 3, 15) } as Task,
                        { start: new Date(2020, 4, 15) } as Task,
                    ],
                },
            );

            expect(first_date.getMonth()).toBe(1);
        });

        it("should ignore the iterations that  ends before the first task date", () => {
            const first_date = getters.first_date(
                {},
                {},
                {
                    iterations: {
                        lvl1_iterations: [
                            {
                                start: new Date(2020, 1, 15),
                                end: new Date(2020, 2, 15),
                            } as Iteration,
                            {
                                start: new Date(2020, 5, 15),
                                end: new Date(2020, 6, 15),
                            } as Iteration,
                        ],
                        lvl2_iterations: [
                            {
                                start: new Date(2020, 2, 15),
                                end: new Date(2020, 5, 15),
                            } as Iteration,
                            {
                                start: new Date(2020, 6, 15),
                                end: new Date(2020, 7, 15),
                            } as Iteration,
                        ],
                    },
                    now: new Date(2020, 3, 20),
                } as RootState,
                {
                    "tasks/tasks": [
                        { start: new Date(2020, 3, 15) } as Task,
                        { start: new Date(2020, 4, 15) } as Task,
                    ],
                },
            );

            expect(first_date.getMonth()).toBe(2);
        });
    });

    describe("last_date", () => {
        it("should return the last date among tasks and iterations", () => {
            const last_date = getters.last_date(
                {},
                {},
                {
                    iterations: {
                        lvl1_iterations: [
                            { start: new Date(2020, 2, 15) } as Iteration,
                            { start: new Date(2020, 5, 15) } as Iteration,
                        ],
                        lvl2_iterations: [
                            { start: new Date(2020, 3, 15) } as Iteration,
                            { start: new Date(2020, 6, 15) } as Iteration,
                        ],
                    },
                    now: new Date(2020, 3, 20),
                } as RootState,
                {
                    "tasks/tasks": [
                        { start: new Date(2020, 3, 15) } as Task,
                        { start: new Date(2020, 4, 15) } as Task,
                    ],
                },
            );
            expect(last_date.getMonth()).toBe(6);
        });
    });

    describe("time_period", () => {
        it("should return a TimePeriod based on weeks", () => {
            const state: TimeperiodState = {
                timescale: "week",
            };

            const first_date = new Date(2020, 3, 15);
            const last_date = new Date(2020, 4, 20);

            const root_state: RootState = {
                gettext_provider: {} as VueGettextProvider,
                locale_bcp47: "fr-FR",
            } as RootState;

            const time_period = getters.time_period(state, { first_date, last_date }, root_state);

            expect(time_period).toBeInstanceOf(TimePeriodWeek);
            expect(time_period.units).toHaveLength(8);
        });

        it("should return a TimePeriod based on months", () => {
            const state: TimeperiodState = {
                timescale: "month",
            };

            const first_date = new Date(2020, 3, 15);
            const last_date = new Date(2020, 4, 20);

            const root_state: RootState = {
                gettext_provider: {} as VueGettextProvider,
                locale_bcp47: "fr-FR",
            } as RootState;

            const time_period = getters.time_period(state, { first_date, last_date }, root_state);

            expect(time_period).toBeInstanceOf(TimePeriodMonth);
            expect(time_period.units).toHaveLength(4);
        });

        it("should return a TimePeriod based on quarters", () => {
            const state: TimeperiodState = {
                timescale: "quarter",
            };

            const first_date = new Date(2020, 3, 15);
            const last_date = new Date(2020, 4, 20);

            const root_state: RootState = {
                gettext_provider: {} as VueGettextProvider,
                locale_bcp47: "fr-FR",
            } as RootState;

            const time_period = getters.time_period(state, { first_date, last_date }, root_state);

            expect(time_period).toBeInstanceOf(TimePeriodQuarter);
            expect(time_period.units).toHaveLength(3);
        });
    });
});
