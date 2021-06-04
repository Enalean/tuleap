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

describe("timeperiod-getters", () => {
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
            expect(time_period.units.length).toBe(8);
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
            expect(time_period.units.length).toBe(4);
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
            expect(time_period.units.length).toBe(3);
        });
    });
});
