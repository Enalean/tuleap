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

import Vue                             from 'vue';
import ReadingMode                     from './ReadingMode.vue';
import { rewire$isAnonymous, restore } from '../user-service.js';
import BackendCrossTrackerReport       from '../backend-cross-tracker-report.js';
import ReadingCrossTrackerReport       from './reading-cross-tracker-report.js';

describe('ReadingMode', () => {
    let ReadingModeElement,
        isAnonymous,
        backendCrossTrackerReport,
        readingCrossTrackerReport,
        queryResultController;

    beforeEach(() => {
        const report_id           = 26;
        ReadingModeElement        = Vue.extend(ReadingMode);
        backendCrossTrackerReport = new BackendCrossTrackerReport(report_id);
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        queryResultController     = jasmine.createSpyObj("queryResultController", ["init"]);
    });

    function instantiateComponent() {
        const vm = new ReadingModeElement({
            propsData: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
                queryResultController
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

        it('When I switch to the writing mode, then an event will be emitted', () => {
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.switchToWritingMode();

            expect(vm.$emit).toHaveBeenCalledWith('switchToWritingMode');
        });

        it("Given I am browsing anonymously, when I try to switch to writing mode, nothing will happen", () => {
            isAnonymous.and.returnValue(true);
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.switchToWritingMode();

            expect(vm.$emit).not.toHaveBeenCalled();
        });
    });
});
