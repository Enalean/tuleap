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

import Vue         from 'vue';
import ReadingMode from './ReadingMode.vue';
import { rewire$isAnonymous, restore } from '../user-service.js';
import ReadingCrossTrackerReport       from './reading-cross-tracker-report.js';
import WritingCrossTrackerReport       from '../writing-mode/writing-cross-tracker-report.js';
import ReportMode                      from '../report-mode.js';
import BackendCrossTrackerReport       from '../backend-cross-tracker-report.js';

describe('ReadingMode', () => {
    let ReadingModeElement,
        isAnonymous,
        backendCrossTrackerReport,
        readingCrossTrackerReport,
        writingCrossTrackerReport,
        reportMode;

    beforeEach(() => {
        const report_id           = 26;
        ReadingModeElement        = Vue.extend(ReadingMode);
        backendCrossTrackerReport = new BackendCrossTrackerReport(report_id);
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        writingCrossTrackerReport = new WritingCrossTrackerReport();
        reportMode                = new ReportMode();
    });

    function instantiateComponent() {
        const vm = new ReadingModeElement({
            propsData: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
                writingCrossTrackerReport,
                reportMode
            }
        });
        vm.$mount();

        return vm;
    }

    describe("switchToWritingMode() -", () => {
        beforeEach(() => {
            isAnonymous = jasmine.createSpy("isAnonymous").and.returnValue(false);
            rewire$isAnonymous(isAnonymous);

            spyOn(writingCrossTrackerReport, "duplicateFromReport");
            spyOn(reportMode, "switchToWritingMode");
        });

        afterEach(() => {
            restore();
        });

        it('When I switch to the writing mode, then the writing report model will be duplicated from the reading report model', () => {
            const vm = instantiateComponent();

            vm.switchToWritingMode();

            expect(writingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(reportMode.switchToWritingMode).toHaveBeenCalled();
        });

        it("Given I am browing anonymously, when I try to switch to writing mode, nothing will happen", () => {
            isAnonymous.and.returnValue(true);

            const vm = instantiateComponent();

            vm.switchToWritingMode();

            expect(writingCrossTrackerReport.duplicateFromReport).not.toHaveBeenCalled();
        });
    });
});
