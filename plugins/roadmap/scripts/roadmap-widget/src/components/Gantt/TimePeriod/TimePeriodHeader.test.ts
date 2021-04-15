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

describe("TimePeriodHeader", () => {
    it("Headers formatted months", () => {
        const wrapper = shallowMount(TimePeriodHeader, {
            propsData: {
                time_period: new TimePeriodMonth(
                    new Date("2020-03-31T22:00:00.000Z"),
                    new Date("2020-04-30T22:00:00.000Z"),
                    "en_US"
                ),
                nb_additional_units: 2,
            },
        });

        expect(wrapper).toMatchInlineSnapshot(`
            <div class="roadmap-gantt-timeperiod">
              <div data-tlp-tooltip="March 2020" class="roadmap-gantt-timeperiod-unit tlp-tooltip tlp-tooltip-top">
                Mar
              </div>
              <div data-tlp-tooltip="April 2020" class="roadmap-gantt-timeperiod-unit tlp-tooltip tlp-tooltip-top">
                Apr
              </div>
              <div data-tlp-tooltip="May 2020" class="roadmap-gantt-timeperiod-unit tlp-tooltip tlp-tooltip-top">
                May
              </div>
              <div data-tlp-tooltip="June 2020" class="roadmap-gantt-timeperiod-unit tlp-tooltip tlp-tooltip-top">
                Jun
              </div>
              <div data-tlp-tooltip="July 2020" class="roadmap-gantt-timeperiod-unit tlp-tooltip tlp-tooltip-top">
                Jul
              </div>
            </div>
        `);
    });
});
