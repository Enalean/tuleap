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

import { DateTime, Settings } from "luxon";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import { TimePeriodMonth } from "../../../helpers/time-period-month";
import type { RootState } from "../../../store/type";
import type { TimeperiodState } from "../../../store/timeperiod/type";
import { NbUnitsPerYear } from "../../../type";
import TimePeriodHeader from "./TimePeriodHeader.vue";
import TimePeriodUnits from "./TimePeriodUnits.vue";
import TimePeriodYears from "./TimePeriodYears.vue";

Settings.defaultZone = "UTC";

describe("TimePeriodHeader", () => {
    let nb_additional_units: number, time_period: TimePeriodMonth;

    beforeEach(() => {
        nb_additional_units = 2;
        time_period = new TimePeriodMonth(
            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
            "en-US",
        );
    });

    function getWrapper(): VueWrapper {
        return shallowMount(TimePeriodHeader, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        timeperiod_state: {} as TimeperiodState,
                    } as RootState,
                    modules: {
                        timeperiod: {
                            getters: {
                                time_period: () => time_period,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
            props: {
                nb_additional_units,
            },
        });
    }

    it("should display years and units", async () => {
        const wrapper = await getWrapper();

        expect(wrapper.findComponent(TimePeriodYears).props().years).toStrictEqual(
            new NbUnitsPerYear([[2020, 5]]),
        );
        expect(wrapper.findComponent(TimePeriodUnits).props().time_units).toStrictEqual([
            DateTime.fromISO("2020-03-01T00:00:00.000Z"),
            DateTime.fromISO("2020-04-01T00:00:00.000Z"),
            DateTime.fromISO("2020-05-01T00:00:00.000Z"),
            DateTime.fromISO("2020-06-01T00:00:00.000Z"),
            DateTime.fromISO("2020-07-01T00:00:00.000Z"),
        ]);
    });

    it("should count how much units the years are spanning on", async () => {
        nb_additional_units = 0;
        time_period = new TimePeriodMonth(
            DateTime.fromISO("2019-12-15T22:00:00.000Z"),
            DateTime.fromISO("2021-05-15T22:00:00.000Z"),
            "en-US",
        );
        const wrapper = await getWrapper();

        expect(wrapper.findComponent(TimePeriodYears).props().years).toStrictEqual(
            new NbUnitsPerYear([
                [2019, 1],
                [2020, 12],
                [2021, 6],
            ]),
        );
    });
});
