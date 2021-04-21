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
import TimePeriodHeader from "./TimePeriodHeader.vue";
import { TimePeriodMonth } from "../../../helpers/time-period-month";
import TimePeriodYears from "./TimePeriodYears.vue";
import { NbUnitsPerYear } from "../../../type";
import TimePeriodUnits from "./TimePeriodUnits.vue";

describe("TimePeriodHeader", () => {
    it("should display years and units", () => {
        const wrapper = shallowMount(TimePeriodHeader, {
            propsData: {
                time_period: new TimePeriodMonth(
                    new Date("2020-03-31T22:00:00.000Z"),
                    new Date("2020-04-30T22:00:00.000Z"),
                    "en-US"
                ),
                nb_additional_units: 2,
            },
        });

        expect(wrapper.findComponent(TimePeriodYears).props().years).toEqual(
            new NbUnitsPerYear([[2020, 5]])
        );
        expect(wrapper.findComponent(TimePeriodUnits).props().time_units).toEqual([
            new Date("2020-03-01T00:00:00.000Z"),
            new Date("2020-04-01T00:00:00.000Z"),
            new Date("2020-05-01T00:00:00.000Z"),
            new Date("2020-06-01T00:00:00.000Z"),
            new Date("2020-07-01T00:00:00.000Z"),
        ]);
    });

    it("should count how much units the years are spanning on", () => {
        const wrapper = shallowMount(TimePeriodHeader, {
            propsData: {
                time_period: new TimePeriodMonth(
                    new Date("2019-12-15T22:00:00.000Z"),
                    new Date("2021-05-15T22:00:00.000Z"),
                    "en-US"
                ),
                nb_additional_units: 0,
            },
        });

        expect(wrapper.findComponent(TimePeriodYears).props().years).toEqual(
            new NbUnitsPerYear([
                [2019, 1],
                [2020, 12],
                [2021, 6],
            ])
        );
    });
});
