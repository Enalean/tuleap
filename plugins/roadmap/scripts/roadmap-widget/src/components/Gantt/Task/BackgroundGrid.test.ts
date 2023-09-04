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
import BackgroundGrid from "./BackgroundGrid.vue";
import { TimePeriodMonth } from "../../../helpers/time-period-month";

describe("BackgroundGrid", () => {
    it("Display the grid to help user distinguish months", () => {
        const wrapper = shallowMount(BackgroundGrid, {
            propsData: {
                time_period: new TimePeriodMonth(
                    new Date("2020-03-31T22:00:00.000Z"),
                    new Date("2020-04-30T22:00:00.000Z"),
                    "en-US",
                ),
                nb_additional_units: 2,
            },
        });

        expect(wrapper).toMatchInlineSnapshot(`
            <div class="roadmap-gantt-task-background-grid">
              <div class="roadmap-gantt-task-background-grid-unit"></div>
              <div class="roadmap-gantt-task-background-grid-unit"></div>
              <div class="roadmap-gantt-task-background-grid-unit"></div>
              <div class="roadmap-gantt-task-background-grid-unit"></div>
              <div class="roadmap-gantt-task-background-grid-unit"></div>
            </div>
        `);
    });
});
