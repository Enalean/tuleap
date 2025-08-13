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
import TimePeriodYears from "./TimePeriodYears.vue";
import { NbUnitsPerYear } from "../../../type";

describe("TimePeriodYears", () => {
    it("should display each year spanning on n units", () => {
        const wrapper = shallowMount(TimePeriodYears, {
            props: {
                years: new NbUnitsPerYear([
                    [2020, 1],
                    [2021, 12],
                    [2022, 6],
                ]),
            },
        });

        expect(wrapper).toMatchInlineSnapshot(`
<div class="roadmap-gantt-timeperiod">
  <div class="roadmap-gantt-timeperiod-year roadmap-gantt-timeperiod-year-span-1">2020</div>
  <div class="roadmap-gantt-timeperiod-year roadmap-gantt-timeperiod-year-span-12">2021</div>
  <div class="roadmap-gantt-timeperiod-year roadmap-gantt-timeperiod-year-span-6">2022</div>
</div>
`);
    });
});
