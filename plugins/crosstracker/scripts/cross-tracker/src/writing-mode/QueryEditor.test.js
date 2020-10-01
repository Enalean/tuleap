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

import { shallowMount } from "@vue/test-utils";
import { localVue } from "../helpers/local-vue";
import QueryEditor from "./QueryEditor.vue";
import WritingCrossTrackerReport from "./writing-cross-tracker-report.js";

describe("QueryEditor", () => {
    let writingCrossTrackerReport;

    beforeEach(() => {
        writingCrossTrackerReport = new WritingCrossTrackerReport();
    });

    function instantiateComponent() {
        return shallowMount(QueryEditor, {
            localVue,
            propsData: {
                writingCrossTrackerReport,
            },
        });
    }

    describe("mounted()", () => {
        it(`When the code mirror instance's value changes,
            then the writing report is updated`, () => {
            // eslint-disable-next-line no-undef
            global.document.createRange = () => {
                return {
                    getBoundingClientRect: () => {},
                    setEnd: () => {},
                    setStart: () => {},
                    getClientRects() {
                        return { length: 0 };
                    },
                };
            };

            const wrapper = instantiateComponent();
            const expert_query = "@title = 'foo'";

            wrapper.vm.code_mirror_instance.setValue(expert_query);

            expect(writingCrossTrackerReport.expert_query).toEqual(expert_query);
        });
    });

    describe("search()", () => {
        it("When I search, then an event will be emitted", () => {
            const wrapper = instantiateComponent();

            wrapper.vm.search();

            expect(wrapper.emitted("trigger-search")).toBeTruthy();
        });
    });
});
