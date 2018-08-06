/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import Vue from "vue";
import { DateTime } from "luxon";
import TimetrackingWidget from "./Widget.vue";

describe("Widget", () => {
    let Widget;

    beforeEach(() => {
        Widget = Vue.extend(TimetrackingWidget);
    });

    function instantiateComponent() {
        return new Widget().$mount();
    }

    describe("Widget initialization", () => {
        it("When the widget is instanciated, Then its end_date must equal to the current date and start_date must equal to end_date minus one week", () => {
            const vm = instantiateComponent();
            const today = DateTime.local().toISODate();
            const last_week = DateTime.local()
                .minus({ weeks: 1 })
                .toISODate();

            expect(vm.start_date).toEqual(last_week);
            expect(vm.end_date).toEqual(today);
        });
    });

    describe("switchToReadingMode", () => {
        it("Given a widget in writing mode, When I switch to the reading mode, Then the reading mode is shown", () => {
            const vm = instantiateComponent();

            vm.reading_mode = false;

            vm.switchToReadingMode();

            expect(vm.reading_mode).toBe(true);
        });

        it("Given a widget in writing mode and some data, When I switch to the reading mode, Then the reading mode is shown", () => {
            const vm = instantiateComponent();

            vm.reading_mode = false;

            vm.switchToReadingMode({
                start_date: "2018-01-01",
                end_date: "2018-01-08"
            });

            expect(vm.reading_mode).toBe(true);
            expect(vm.start_date).toEqual("2018-01-01");
            expect(vm.end_date).toEqual("2018-01-08");
        });
    });
});
