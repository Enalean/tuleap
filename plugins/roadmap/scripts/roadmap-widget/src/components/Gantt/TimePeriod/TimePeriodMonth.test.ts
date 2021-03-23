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
import TimePeriodMonth from "./TimePeriodMonth.vue";

describe("TimePeriodeMonth", () => {
    it("Displays formatted months", () => {
        const wrapper = shallowMount(TimePeriodMonth, {
            propsData: {
                locale: "en_US",
                months: [new Date(2020, 3, 1), new Date(2020, 4, 1)],
            },
        });

        expect(wrapper).toMatchInlineSnapshot(`
            <div class="roadmap-gantt-timeperiod-months">
              <div data-tlp-tooltip="April 2020" class="roadmap-gantt-timeperiod-month tlp-tooltip tlp-tooltip-bottom">
                Apr
              </div>
              <div data-tlp-tooltip="May 2020" class="roadmap-gantt-timeperiod-month tlp-tooltip tlp-tooltip-bottom">
                May
              </div>
            </div>
        `);
    });
});
