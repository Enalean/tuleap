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

import Vue                             from 'vue';
import CrossTrackerWidget              from './CrossTrackerWidget.vue';
import { rewire$isAnonymous, restore } from './user-service.js';
import BackendCrossTrackerReport       from './backend-cross-tracker-report.js';
import ReadingCrossTrackerReport       from './reading-mode/reading-cross-tracker-report.js';
import WritingCrossTrackerReport       from './writing-mode/writing-cross-tracker-report.js';
import SavedState                      from './report-saved-state.js';

describe("CrossTrackerWidget", () => {
    let Widget,
        isAnonymous,
        backendCrossTrackerReport,
        readingCrossTrackerReport,
        writingCrossTrackerReport,
        successDisplayer,
        errorDisplayer,
        savedState,
        queryResultController,
        readingController;

    beforeEach(() => {
        const report_id = 86;
        Widget = Vue.extend(CrossTrackerWidget);
        backendCrossTrackerReport = new BackendCrossTrackerReport(report_id);
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        writingCrossTrackerReport = new WritingCrossTrackerReport();
        savedState                = new SavedState();

        successDisplayer           = jasmine.createSpyObj("successDisplayer", ["hideSuccess"]);
        errorDisplayer             = jasmine.createSpyObj("errorDisplayer", ["hideError"]);
        queryResultController      = jasmine.createSpyObj("queryResultController", ["init", "loadFirstBatchOfArtifacts"]);
        readingController          = jasmine.createSpyObj("readingController", ["init"]);

        spyOn(writingCrossTrackerReport, "duplicateFromReport");
        spyOn(readingCrossTrackerReport, "duplicateFromReport");
    });

    function instantiateComponent() {
        const vm = new Widget({
            propsData: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
                writingCrossTrackerReport,
                successDisplayer,
                errorDisplayer,
                savedState,
                queryResultController,
                readingController,
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
            restore();
        });

        it("when I switch to the writing mode, then the  writing report will be updated and the feedbacks hidden", () => {
            const vm = instantiateComponent();

            vm.switchToWritingMode();

            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(successDisplayer.hideSuccess).toHaveBeenCalled();
            expect(errorDisplayer.hideError).toHaveBeenCalled();
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
        it("When I switch to the reading mode with saved state, then the writing report will be updated, the reading action buttons hidden and the feedbacks hidden", () => {
            const vm = instantiateComponent();
            spyOn(vm.$refs.reading_mode, "hideActions");
            spyOn(savedState, "switchToSavedState");

            vm.switchToReadingMode({ saved_state: true });

            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(savedState.switchToSavedState).toHaveBeenCalled();
            expect(vm.$refs.reading_mode.hideActions).toHaveBeenCalled();
            expect(successDisplayer.hideSuccess).toHaveBeenCalled();
            expect(errorDisplayer.hideError).toHaveBeenCalled();
            expect(vm.reading_mode).toBe(true);
        });

        it("When I switch to the reading mode with unsaved state, then a batch of artifacts will be loaded, the reading report will be updated, the reading action buttons shown and the feedbacks hidden", () => {
            const vm = instantiateComponent();
            spyOn(vm.$refs.reading_mode, "showActions");
            spyOn(savedState, "switchToUnsavedState");

            vm.switchToReadingMode({ saved_state: false });

            expect(queryResultController.loadFirstBatchOfArtifacts).toHaveBeenCalled();
            expect(readingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(writingCrossTrackerReport);
            expect(savedState.switchToUnsavedState).toHaveBeenCalled();
            expect(vm.$refs.reading_mode.showActions).toHaveBeenCalled();
            expect(successDisplayer.hideSuccess).toHaveBeenCalled();
            expect(errorDisplayer.hideError).toHaveBeenCalled();
            expect(vm.reading_mode).toBe(true);
        });
    });
});
