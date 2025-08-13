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

import { DateTime } from "luxon";
import { shallowMount } from "@vue/test-utils";
import type { Iteration } from "../../../type";
import type { TimeperiodState } from "../../../store/timeperiod/type";
import type { RootState } from "../../../store/type";
import { TimePeriodMonth } from "../../../helpers/time-period-month";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import IterationsRibbon from "./IterationsRibbon.vue";
import IterationBar from "./IterationBar.vue";

describe("IterationsRibbon", () => {
    it("should display all iterations", () => {
        const wrapper = shallowMount(IterationsRibbon, {
            props: {
                nb_additional_units: 0,
                level: 1,
                iterations: [
                    { id: 1 } as Iteration,
                    { id: 2 } as Iteration,
                    { id: 3 } as Iteration,
                ],
            },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        timeperiod_state: {} as TimeperiodState,
                    } as RootState,
                    modules: {
                        timeperiod: {
                            getters: {
                                time_period: () =>
                                    new TimePeriodMonth(
                                        DateTime.fromISO("2020-01-01T13:42:08+02:00"),
                                        DateTime.fromISO("2020-01-30T13:42:08+02:00"),
                                        "en-US",
                                    ),
                            },
                        },
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(IterationBar)).toHaveLength(3);
    });
});
