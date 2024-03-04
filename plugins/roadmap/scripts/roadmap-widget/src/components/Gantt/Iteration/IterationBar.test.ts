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

import { shallowMount } from "@vue/test-utils";
import IterationBar from "./IterationBar.vue";
import type { Iteration, Row } from "../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { RootState } from "../../../store/type";
import { TimePeriodMonth } from "../../../helpers/time-period-month";
import type { TimeperiodState } from "../../../store/timeperiod/type";
import type { IterationsState } from "../../../store/iterations/type";
import type { TasksState } from "../../../store/tasks/type";
import { DateTime } from "luxon";

describe("IterationBar", () => {
    describe("should adjust the height of the iteration according to the number of visible rows so that we have borders of the iteration that take all the height", () => {
        it("when there is many rows", () => {
            const wrapper = shallowMount(IterationBar, {
                propsData: {
                    iteration: {
                        start: DateTime.fromISO("2020-01-10T13:42:08+02:00"),
                        end: DateTime.fromISO("2020-01-20T13:42:08+02:00"),
                        html_url: "/path/to/iteration",
                    } as Iteration,
                    level: 2,
                },
                mocks: {
                    $store: createStoreMock({
                        state: {
                            timeperiod: {} as TimeperiodState,
                            iterations: {} as IterationsState,
                            tasks: {} as TasksState,
                        } as RootState,
                        getters: {
                            "timeperiod/time_period": new TimePeriodMonth(
                                DateTime.fromISO("2020-01-01T13:42:08+02:00"),
                                DateTime.fromISO("2020-01-30T13:42:08+02:00"),
                                "en-US",
                            ),
                            "tasks/rows": [
                                { is_shown: true } as Row,
                                { is_shown: false } as Row,
                                { is_shown: true } as Row,
                            ],
                        },
                    }),
                },
            });

            expect((wrapper.element as HTMLElement).style.height).toBe("106px");
        });

        it("when there are many visible rows but the iteration is at level 1 and there is another level under", () => {
            const wrapper = shallowMount(IterationBar, {
                propsData: {
                    iteration: {
                        start: DateTime.fromISO("2020-01-10T13:42:08+02:00"),
                        end: DateTime.fromISO("2020-01-20T13:42:08+02:00"),
                        html_url: "/path/to/iteration",
                    } as Iteration,
                    level: 1,
                },
                mocks: {
                    $store: createStoreMock({
                        state: {
                            timeperiod: {} as TimeperiodState,
                            iterations: {
                                lvl2_iterations: [{} as Iteration],
                            } as IterationsState,
                            tasks: {} as TasksState,
                        } as RootState,
                        getters: {
                            "timeperiod/time_period": new TimePeriodMonth(
                                DateTime.fromISO("2020-01-01T13:42:08+02:00"),
                                DateTime.fromISO("2020-01-30T13:42:08+02:00"),
                                "en-US",
                            ),
                            "tasks/rows": [
                                { is_shown: true } as Row,
                                { is_shown: false } as Row,
                                { is_shown: true } as Row,
                            ],
                        },
                    }),
                },
            });

            expect((wrapper.element as HTMLElement).style.height).toBe("130px");
        });
    });
});
