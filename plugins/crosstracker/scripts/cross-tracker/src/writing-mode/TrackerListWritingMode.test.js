/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
import TrackerListWritingMode from "./TrackerListWritingMode.vue";

describe("TrackerListWritingMode", () => {
    let TrackerList;

    beforeEach(() => {
        TrackerList = Vue.extend(TrackerListWritingMode);
    });

    function instantiateComponent() {
        const vm = new TrackerList();
        vm.$mount();

        return vm;
    }

    describe("removeTracker()", () => {
        it("when I remove a tracker, then an event will be emitted", () => {
            const vm = instantiateComponent();
            jest.spyOn(vm, "$emit").mockImplementation(() => {});
            const tracker = { tracker_label: "fake_tracker" };

            vm.removeTracker(tracker);

            expect(vm.$emit).toHaveBeenCalledWith("trackerRemoved", tracker);
        });
    });
});
