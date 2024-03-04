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
import TimePeriodUnits from "./TimePeriodUnits.vue";
import { TimePeriodMonth } from "../../../helpers/time-period-month";
import { DateTime, Settings } from "luxon";

Settings.defaultZone = "UTC";

describe("TimePeriodUnits", () => {
    it("should display units", () => {
        const time_period = new TimePeriodMonth(
            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
            "en-US",
        );

        const time_units = [...time_period.units, ...time_period.additionalUnits(2)];

        const wrapper = shallowMount(TimePeriodUnits, {
            propsData: {
                time_period,
                time_units,
            },
        });

        expect(wrapper).toMatchInlineSnapshot(`
            <div class="roadmap-gantt-timeperiod">
              <div title="March 2020" class="roadmap-gantt-timeperiod-unit">
                Mar
              </div>
              <div title="April 2020" class="roadmap-gantt-timeperiod-unit">
                Apr
              </div>
              <div title="May 2020" class="roadmap-gantt-timeperiod-unit">
                May
              </div>
              <div title="June 2020" class="roadmap-gantt-timeperiod-unit">
                Jun
              </div>
              <div title="July 2020" class="roadmap-gantt-timeperiod-unit">
                Jul
              </div>
            </div>
        `);
    });
});
