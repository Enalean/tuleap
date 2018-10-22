/*
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
import { mockFetchError } from "tlp-mocks";
import { createStore } from "./store/index.js";
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import { restore as restoreUser, rewire$isAnonymous } from "./user-service.js";
import BackendCrossTrackerReport from "./backend-cross-tracker-report.js";
import ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report.js";
import WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report.js";
import {
    restore as restoreRest,
    rewire$getQueryResult,
    rewire$getReport,
    rewire$getReportContent
} from "./rest-querier.js";

describe("CrossTrackerWidget", () => {
    let Widget,
        isAnonymous,
        backendCrossTrackerReport,
        readingCrossTrackerReport,
        writingCrossTrackerReport,
        getReport,
        getReportContent,
        getQueryResult;

    beforeEach(() => {
        Widget = Vue.extend(CrossTrackerWidget);
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        writingCrossTrackerReport = new WritingCrossTrackerReport();

        spyOn(writingCrossTrackerReport, "duplicateFromReport");
        spyOn(readingCrossTrackerReport, "duplicateFromReport");

        getReportContent = jasmine.createSpy("getReportContent");
        rewire$getReportContent(getReportContent);

        getQueryResult = jasmine.createSpy("getQueryResult");
        rewire$getQueryResult(getQueryResult);
    });

    afterEach(() => {
        restoreRest();
    });

    function instantiateComponent() {
        const vm = new Widget({
            store: createStore(),
            propsData: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
                writingCrossTrackerReport
            }
        });
        vm.$mount();
        spyOn(vm.$store, "commit");

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

        it("when I switch to the writing mode, then the  writing report will be updated and a mutation will be committed", () => {
            const vm = instantiateComponent();

            vm.switchToWritingMode();

            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                readingCrossTrackerReport
            );
            expect(vm.$store.commit).toHaveBeenCalledWith("switchToWritingMode");
        });

        it("Given I am browsing anonymously, when I try to switch to writing mode, then nothing will happen", () => {
            isAnonymous.and.returnValue(true);
            const vm = instantiateComponent();

            vm.switchToWritingMode();

            expect(writingCrossTrackerReport.duplicateFromReport).not.toHaveBeenCalled();
            expect(vm.reading_mode).toBe(true);
        });
    });

    describe("switchToReadingMode() -", () => {
        it("When I switch to the reading mode with saved state, then the writing report will be updated and a mutation will be committed", () => {
            const vm = instantiateComponent();

            const payload = { saved_state: true };
            vm.switchToReadingMode(payload);

            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                readingCrossTrackerReport
            );
            expect(vm.$store.commit).toHaveBeenCalledWith("switchToReadingMode", payload);
        });

        it("When I switch to the reading mode with unsaved state, then a batch of artifacts will be loaded, the reading report will be updated and a mutation will be committed", () => {
            const vm = instantiateComponent();

            const payload = { saved_state: false };
            vm.switchToReadingMode(payload);

            expect(readingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                writingCrossTrackerReport
            );
            expect(vm.$store.commit).toHaveBeenCalledWith("switchToReadingMode", payload);
        });
    });

    describe("loadBackendReport() -", () => {
        beforeEach(() => {
            getReport = jasmine.createSpy("getReport");
            rewire$getReport(getReport);
        });

        it("When I load the report, then the reports will be initialized", async () => {
            const trackers = [{ id: 25 }, { id: 30 }];
            const expert_query = '@title != ""';
            getReport.and.returnValue({
                trackers,
                expert_query
            });
            spyOn(backendCrossTrackerReport, "init");
            const vm = instantiateComponent();

            const promise = vm.loadBackendReport();
            expect(vm.is_loading).toBe(true);

            await promise;

            expect(vm.is_loading).toBe(false);
            expect(backendCrossTrackerReport.init).toHaveBeenCalledWith(trackers, expert_query);
            expect(readingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                backendCrossTrackerReport
            );
            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                readingCrossTrackerReport
            );
        });

        it("When there is a REST error, it will be shown", () => {
            const message = "Report 41 not found";
            mockFetchError(getReport, {
                error_json: {
                    error: { message }
                }
            });
            const vm = instantiateComponent();

            return vm.loadBackendReport().then(() => {
                expect(vm.$store.commit).toHaveBeenCalledWith("setErrorMessage", message);
            });
        });
    });

    describe("reportSaved() -", () => {
        it("when the report is saved, then the reports will be updated and a mutation will be committed", () => {
            const vm = instantiateComponent();

            vm.reportSaved();

            expect(readingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                backendCrossTrackerReport
            );
            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                readingCrossTrackerReport
            );
            expect(vm.$store.commit).toHaveBeenCalledWith(
                "switchReportToSaved",
                jasmine.any(String)
            );
        });
    });
});
