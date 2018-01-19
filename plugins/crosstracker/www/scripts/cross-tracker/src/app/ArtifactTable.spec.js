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

import Vue                       from 'vue';
import WritingCrossTrackerReport from './writing-mode/writing-cross-tracker-report.js';
import ArtifactTable             from './ArtifactTable.vue';
import {
    rewire$getReportContent,
    rewire$getQueryResult,
    restore
} from './rest-querier.js';

describe("ArtifactTable", () => {
    let Widget,
        reportId,
        writingCrossTrackerReport,
        isReportSaved;

    beforeEach(() => {
        Widget                    = Vue.extend(ArtifactTable);
        writingCrossTrackerReport = new WritingCrossTrackerReport();
        reportId                  = '86';
        isReportSaved             = true;
    });

    function instantiateComponent() {
        const vm = new Widget({
            propsData: {
                isReportSaved,
                reportId,
                writingCrossTrackerReport,
            }
        });
        vm.$mount();

        return vm;
    }

    describe("loadArtifacts() -", () => {
        let getReport, getQuery;

        beforeEach(() => {
            getReport = jasmine.createSpy('getReportContent');
            rewire$getReportContent(getReport);

            getQuery = jasmine.createSpy('getQueryResult');
            rewire$getQueryResult(getQuery);
        });

        afterEach(() => {
            restore();
        });

        it("Given report is saved, it loads artifacts of report", () => {
            isReportSaved = true;
            const vm = instantiateComponent();

            vm.loadArtifacts();

            expect(getReport).toHaveBeenCalled();
        });

        it("Given report is not saved, it loads artifacts of current selected trackers", () => {
            isReportSaved = false;
            const vm = instantiateComponent();

            vm.loadArtifacts();

            expect(getQuery).toHaveBeenCalled();
        });

        it("when there is a REST error, it will be displayed", () => {
            getReport.and.returnValue(Promise.reject(500));
            const vm = instantiateComponent();

            vm.loadArtifacts().then(() => {
                fail();
            }, () => {
                expect(vm.$emit).toHaveBeenCalledWith('restError', 'Error while fetching the query result');
            });
        });
    });
});
