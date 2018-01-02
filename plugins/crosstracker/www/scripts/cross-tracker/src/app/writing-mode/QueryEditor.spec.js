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
import QueryEditor               from './QueryEditor.vue';
import WritingCrossTrackerReport from './writing-cross-tracker-report.js';

describe("QueryEditor", () => {
    let QueryEditorElement,
        writingCrossTrackerReport;

    beforeEach(() => {
        QueryEditorElement        = Vue.extend(QueryEditor);
        writingCrossTrackerReport = new WritingCrossTrackerReport();
    });

    function instantiateComponent() {
        const vm = new QueryEditorElement({
            propsData: {
                writingCrossTrackerReport
            }
        });
        vm.$mount();

        return vm;
    }

    describe("mounted()", () => {
        it("When the code mirror instance's value changes, then the writing report is updated", () => {
            const vm = instantiateComponent();
            const expert_query = "@title = 'foo'";

            vm.code_mirror_instance.setValue(expert_query);

            expect(writingCrossTrackerReport.expert_query).toEqual(expert_query);
        });
    });

    describe("refresh()", () => {
        it("When I refresh, then the code mirror instance is refreshed and its value is updated", () => {
            jasmine.clock().install();
            const vm = instantiateComponent();
            spyOn(vm.code_mirror_instance, "refresh");
            spyOn(vm.code_mirror_instance, "setValue");

            vm.refresh();
            jasmine.clock().tick(1);

            expect(vm.code_mirror_instance.setValue).toHaveBeenCalledWith(writingCrossTrackerReport.expert_query);
            expect(vm.code_mirror_instance.refresh).toHaveBeenCalled();

            jasmine.clock().uninstall();
        });
    });

    describe("search()", () => {
        it("When I search, then an event will be emitted", () => {
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.search();

            expect(vm.$emit).toHaveBeenCalledWith('triggerSearch');
        });
    });
});
