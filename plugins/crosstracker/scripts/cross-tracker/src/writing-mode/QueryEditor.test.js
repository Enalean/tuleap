/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import GetTextPlugin from "vue-gettext";
import QueryEditor from "./QueryEditor.vue";
import WritingCrossTrackerReport from "./writing-cross-tracker-report.js";

describe("QueryEditor", () => {
    let QueryEditorElement, writingCrossTrackerReport;

    beforeEach(() => {
        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });
        QueryEditorElement = Vue.extend(QueryEditor);
        writingCrossTrackerReport = new WritingCrossTrackerReport();
    });

    function instantiateComponent() {
        const vm = new QueryEditorElement({
            propsData: {
                writingCrossTrackerReport,
            },
        });
        vm.$mount();

        return vm;
    }

    describe("mounted()", () => {
        it("When the code mirror instance's value changes, then the writing report is updated", () => {
            // eslint-disable-next-line no-undef
            global.document.body.createTextRange = () => {
                return {
                    getBoundingClientRect: () => {},
                    getClientRects() {
                        return { length: 0 };
                    },
                };
            };

            const vm = instantiateComponent();
            const expert_query = "@title = 'foo'";

            vm.code_mirror_instance.setValue(expert_query);

            expect(writingCrossTrackerReport.expert_query).toEqual(expert_query);
        });
    });

    describe("search()", () => {
        it("When I search, then an event will be emitted", () => {
            const vm = instantiateComponent();
            jest.spyOn(vm, "$emit").mockImplementation(() => {});

            vm.search();

            expect(vm.$emit).toHaveBeenCalledWith("triggerSearch");
        });
    });
});
