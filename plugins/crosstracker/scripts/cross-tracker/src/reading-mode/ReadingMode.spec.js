/*
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
import { createStore } from "../store/index.js";
import ReadingMode from "./ReadingMode.vue";
import { rewire$isAnonymous, restore as restoreUser } from "../user-service.js";
import BackendCrossTrackerReport from "../backend-cross-tracker-report.js";
import ReadingCrossTrackerReport from "./reading-cross-tracker-report.js";
import { rewire$updateReport, restore as restoreRest } from "../rest-querier.js";

describe("ReadingMode", () => {
    let ReadingModeElement,
        isAnonymous,
        backendCrossTrackerReport,
        readingCrossTrackerReport,
        isReportInError,
        updateReport;

    beforeEach(() => {
        ReadingModeElement = Vue.extend(ReadingMode);
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        isReportInError = false;
    });

    function instantiateComponent() {
        const vm = new ReadingModeElement({
            store: createStore(),
            propsData: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
                isReportInError
            }
        });
        vm.$mount();

        return vm;
    }

    describe("switchToWritingMode() -", () => {
        beforeEach(() => {
            isAnonymous = jasmine.createSpy("isAnonymous").and.returnValue(false);
            rewire$isAnonymous(isAnonymous);
        });

        afterEach(() => {
            restoreUser();
        });

        it("When I switch to the writing mode, then an event will be emitted", () => {
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.switchToWritingMode();

            expect(vm.$emit).toHaveBeenCalledWith("switchToWritingMode");
        });

        it("Given I am browsing anonymously, when I try to switch to writing mode, nothing will happen", () => {
            isAnonymous.and.returnValue(true);
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.switchToWritingMode();

            expect(vm.$emit).not.toHaveBeenCalled();
        });
    });

    describe("saveReport() -", () => {
        beforeEach(() => {
            updateReport = jasmine.createSpy("updateReport");
            rewire$updateReport(updateReport);
        });

        afterEach(() => {
            restoreRest();
        });

        it("When I save the report, the backend report will be updated and an event will be emitted", async () => {
            spyOn(backendCrossTrackerReport, "init");
            spyOn(backendCrossTrackerReport, "duplicateFromReport");
            const trackers = [{ id: 36 }, { id: 17 }];
            const expert_query = '@description != ""';
            updateReport.and.returnValue({
                trackers,
                expert_query
            });
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            const promise = vm.saveReport();
            expect(vm.is_loading).toBe(true);

            await promise;
            expect(backendCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                readingCrossTrackerReport
            );
            expect(backendCrossTrackerReport.init).toHaveBeenCalledWith(trackers, expert_query);
            expect(updateReport).toHaveBeenCalled();
            expect(vm.$emit).toHaveBeenCalledWith("saved");
            expect(vm.is_loading).toBe(false);
        });

        it("Given the report is in error, then nothing will happen", async () => {
            isReportInError = true;
            const vm = instantiateComponent();

            await vm.saveReport();
            expect(updateReport).not.toHaveBeenCalled();
        });

        it("When there is a REST error, an event will be emitted", () => {
            updateReport.and.returnValue(Promise.reject(500));
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.saveReport().then(
                () => {
                    fail();
                },
                () => {
                    expect(vm.$emit).toHaveBeenCalledWith("restError", 500);
                }
            );
        });
    });

    describe("cancelReport() -", () => {
        it("when I click on 'Cancel', then the reading report will be reset and an event will be emitted", () => {
            spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.cancelReport();

            expect(readingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                backendCrossTrackerReport
            );
            expect(vm.$emit).toHaveBeenCalledWith("cancelled");
        });
    });
});
