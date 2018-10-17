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
        reportId,
        getReport,
        getReportContent,
        getQueryResult;

    beforeEach(() => {
        Widget = Vue.extend(CrossTrackerWidget);
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        writingCrossTrackerReport = new WritingCrossTrackerReport();
        reportId = "86";

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
                writingCrossTrackerReport,
                reportId
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

        it("when I switch to the writing mode, then the  writing report will be updated and the feedbacks hidden", () => {
            const vm = instantiateComponent();

            vm.switchToWritingMode();

            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                readingCrossTrackerReport
            );
            expect(vm.error_message).toBe(null);
            expect(vm.success_message).toBe(null);
            expect(vm.reading_mode).toBe(false);
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
        it("When I switch to the reading mode with saved state, then the writing report will be updated and the feedbacks hidden", () => {
            const vm = instantiateComponent();

            vm.switchToReadingMode({ saved_state: true });

            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                readingCrossTrackerReport
            );
            expect(vm.is_saved).toBe(true);
            expect(vm.error_message).toBe(null);
            expect(vm.success_message).toBe(null);
            expect(vm.reading_mode).toBe(true);
        });

        it("When I switch to the reading mode with unsaved state, then a batch of artifacts will be loaded, the reading report will be updated and the feedbacks hidden", () => {
            const vm = instantiateComponent();

            vm.switchToReadingMode({ saved_state: false });

            expect(readingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                writingCrossTrackerReport
            );
            expect(vm.is_saved).toBe(false);
            expect(vm.error_message).toBe(null);
            expect(vm.success_message).toBe(null);
            expect(vm.reading_mode).toBe(true);
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
            const i18n_error_message = "Error while parsing the query";
            mockFetchError(getReport, {
                error_json: {
                    error: { i18n_error_message }
                }
            });
            const vm = instantiateComponent();

            vm.loadBackendReport().then(
                () => {
                    fail();
                },
                () => {
                    expect(vm.error_message).toEqual(i18n_error_message);
                }
            );
        });

        it("When there is an error in REST error, a generic error message will be shown", () => {
            getReport.and.returnValue(
                Promise.reject({
                    response: {
                        json() {
                            return Promise.reject();
                        }
                    }
                })
            );
            const vm = instantiateComponent();

            vm.loadBackendReport().then(
                () => {
                    fail();
                },
                () => {
                    expect(vm.error_message).toEqual("An error occured");
                }
            );
        });
    });

    describe("reportSaved() -", () => {
        it("when the report is saved, then the feedbacks will be hidden and a success message will be shown", () => {
            const vm = instantiateComponent();

            vm.reportSaved();

            expect(readingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                backendCrossTrackerReport
            );
            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                readingCrossTrackerReport
            );
            expect(vm.error_message).toBe(null);
            expect(vm.is_saved).toBe(true);
            expect(vm.success_message).toEqual(jasmine.any(String));
        });
    });

    describe("reportCancelled() -", () => {
        it("when the 'Cancel' button is clicked in Reading mode, then the feedbacks will be hidden", () => {
            const vm = instantiateComponent();

            vm.reportCancelled();

            expect(vm.error_message).toBe(null);
            expect(vm.success_message).toBe(null);
            expect(vm.is_saved).toBe(true);
        });
    });
});
