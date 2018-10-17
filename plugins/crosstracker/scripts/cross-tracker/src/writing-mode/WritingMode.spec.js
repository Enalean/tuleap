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
import WritingMode from "./WritingMode.vue";
import {
    default as WritingCrossTrackerReport,
    TooManyTrackersSelectedError
} from "./writing-cross-tracker-report.js";

describe("WritingMode", () => {
    let Writing, writingCrossTrackerReport;

    beforeEach(() => {
        Writing = Vue.extend(WritingMode);
        writingCrossTrackerReport = new WritingCrossTrackerReport();
    });

    function instantiateComponent() {
        const vm = new Writing({
            propsData: {
                writingCrossTrackerReport
            }
        });
        vm.$mount();

        return vm;
    }

    describe("mounted()", () => {
        it("on init, the selected trackers will be formatted from the writing report", () => {
            writingCrossTrackerReport.addTracker(
                { id: 804, label: "fanatical" },
                { id: 29, label: "charry" }
            );
            writingCrossTrackerReport.addTracker(
                { id: 146, label: "surly" },
                { id: 51, label: "monodynamism" }
            );

            const vm = instantiateComponent();

            expect(vm.selected_trackers).toEqual([
                {
                    tracker_id: 29,
                    tracker_label: "charry",
                    project_label: "fanatical"
                },
                {
                    tracker_id: 51,
                    tracker_label: "monodynamism",
                    project_label: "surly"
                }
            ]);
        });
    });

    describe("cancel()", () => {
        it("when I hit cancel, then an event will be emitted to switch the widget to reading mode in saved state", () => {
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.cancel();

            expect(vm.$emit).toHaveBeenCalledWith("switchToReadingMode", { saved_state: true });
        });
    });

    describe("search()", () => {
        it("when I hit search, then an event will be emitted to switch the widget to reading mode in unsaved state", () => {
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.search();

            expect(vm.$emit).toHaveBeenCalledWith("switchToReadingMode", { saved_state: false });
        });
    });

    describe("removeTrackerFromSelection()", () => {
        it("when I remove a tracker, then the writing report will be updated and the errors hidden", () => {
            writingCrossTrackerReport.addTracker(
                { id: 172, label: "undiuretic" },
                { id: 61, label: "Dipneumona" }
            );
            writingCrossTrackerReport.addTracker(
                { id: 288, label: "defectless" },
                { id: 46, label: "knothorn" }
            );
            spyOn(writingCrossTrackerReport, "removeTracker").and.callThrough();
            const tracker = { tracker_id: 46 };
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.removeTrackerFromSelection(tracker);

            expect(writingCrossTrackerReport.removeTracker).toHaveBeenCalledWith(46);
            expect(vm.$emit).toHaveBeenCalledWith("clearErrors");
            expect(vm.selected_trackers).toEqual([
                {
                    tracker_id: 61,
                    tracker_label: "Dipneumona",
                    project_label: "undiuretic"
                }
            ]);
        });
    });

    describe("addTrackerToSelection()", () => {
        it("when I add a tracker, then the writing report will be updated", () => {
            spyOn(writingCrossTrackerReport, "addTracker").and.callThrough();
            const vm = instantiateComponent();
            const selected_project = { id: 656, label: "ergatogyne" };
            const selected_tracker = { id: 53, label: "observingly" };

            vm.addTrackerToSelection({
                selected_project,
                selected_tracker
            });

            expect(writingCrossTrackerReport.addTracker).toHaveBeenCalledWith(
                selected_project,
                selected_tracker
            );
            expect(vm.selected_trackers).toEqual([
                {
                    tracker_id: 53,
                    tracker_label: "observingly",
                    project_label: "ergatogyne"
                }
            ]);
        });

        it("Given I had already added 10 trackers, when I try to add another, then an error will be shown", () => {
            spyOn(writingCrossTrackerReport, "addTracker").and.throwError(
                new TooManyTrackersSelectedError()
            );
            const vm = instantiateComponent();
            const selected_project = { id: 656, label: "ergatogyne" };
            const selected_tracker = { id: 53, label: "observingly" };
            spyOn(vm, "$emit");

            vm.addTrackerToSelection({
                selected_project,
                selected_tracker
            });

            expect(vm.$emit).toHaveBeenCalledWith("error", jasmine.any(String));
        });
    });
});
