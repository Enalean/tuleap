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

import Vue                                                from 'vue';
import WritingCrossTrackerReport                          from './writing-mode/writing-cross-tracker-report.js';
import ArtifactTableRenderer                              from './ArtifactTableRenderer.vue';
import { rewire$isReportSaved, restore }                  from './report-saved-state.js';
import { rewire$getReportContent, rewire$getQueryResult } from './rest-querier.js'

describe("ArtifactTableRenderer", () => {
    let Widget,
        report_id,
        writingCrossTrackerReport,
        savedState;

    beforeEach(() => {
        report_id                 = 86;
        Widget                    = Vue.extend(ArtifactTableRenderer);
        writingCrossTrackerReport = new WritingCrossTrackerReport();
        savedState                = jasmine.createSpyObj("savedState", ["isReportSaved"]);
    });

    function instantiateComponent() {
        const vm = new Widget({
            propsData: {
                'savedState': savedState,
                'report_id': report_id,
                'writingCrossTrackerReport':writingCrossTrackerReport,
            }
        });
        vm.$mount();

        return vm;
    }

    describe("loadArtifacts() -", () => {
        let getResultReportContent, getQueryResultReport;

        beforeEach(() => {
            getResultReportContent = jasmine.createSpy('getReportContent');
            rewire$getReportContent(getResultReportContent);

            getQueryResultReport = jasmine.createSpy('getQueryResult');
            rewire$getQueryResult(getQueryResultReport);
        });

        afterEach(() => {
            restore();
        });

        it("Given report is saved, it loads artifacts of report", () => {
            const vm = instantiateComponent();

            savedState.isReportSaved.and.callFake(function() {
                return true;
            });

            vm.loadArtifacts();

            expect(getResultReportContent).toHaveBeenCalled();
        });

        it("Given report is not saved, it loads artifacts of current selected trackers", () => {
            const vm = instantiateComponent();

            savedState.isReportSaved.and.callFake(function() {
                return false;
            });

            vm.loadArtifacts();

            expect(getQueryResultReport).toHaveBeenCalled();
        });

        it("when there is a REST error, it will be displayed", () => {
            getResultReportContent.and.returnValue(Promise.reject(500));
            const vm = instantiateComponent();

            vm.loadArtifacts().then(() => {
                fail();
            }, () => {
                expect(vm.$emit).toHaveBeenCalledWith('error', 'Error while fetching the query result');
            });
        });
    });
});
