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
import WidgetWritingMode from "./WidgetWritingMode.vue";

describe("WidgetWritingMode", () => {
    let WritingMode;

    beforeEach(() => {
        WritingMode = Vue.extend(WidgetWritingMode);
    });

    function instantiateComponent(data = {}) {
        return new WritingMode({
            propsData: { ...data }
        }).$mount();
    }

    describe("Cancel", () => {
        it("When I click on the cancel button, Then an event is emitted without data to broadcast", () => {
            const vm = instantiateComponent();

            spyOn(vm, "$emit");

            vm.cancel();

            expect(vm.$emit).toHaveBeenCalledWith("switchToReadingMode");
        });
    });

    describe("switchToReadingMode", () => {
        it("When I click on the search button, Then the selected dates are broadcast through an event.", () => {
            const vm = instantiateComponent({
                readingStartDate: "2018-01-01",
                readingEndDate: "2018-01-08"
            });

            spyOn(vm, "$emit");

            vm.switchToReadingMode();

            expect(vm.$emit).toHaveBeenCalledWith("switchToReadingMode", {
                start_date: "2018-01-01",
                end_date: "2018-01-08"
            });
        });
    });
});
