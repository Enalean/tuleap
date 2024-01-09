/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

import Vue from "vue";
import { shallowMount } from "@vue/test-utils";
import { createLocalVueForTests } from "../../support/local-vue.ts";
import MilestoneList from "./MilestonesSelect.vue";

describe("MilestonesSelect", () => {
    const milestone_selector = '[data-test-type="milestone"]';

    let wrapper;

    beforeEach(async () => {
        wrapper = shallowMount(MilestoneList, {
            localVue: await createLocalVueForTests(),
            propsData: {
                milestones: [],
            },
            sync: false,
        });
    });

    describe("when many milestones", () => {
        const milestone_1 = { id: 1, label: "first milestone" };
        const milestone_2 = { id: 2, label: "second milestone" };
        const milestone_3 = { id: 3, label: "a milestone" };

        beforeEach(async () => {
            wrapper.setProps({
                milestones: [milestone_2, milestone_1, milestone_3],
            });
            await Vue.nextTick();
        });

        it("shows as many milestones as given", () => {
            let milestones = wrapper.findAll(milestone_selector);
            expect(milestones).toHaveLength(3);
        });

        describe("sorted_milestones", () => {
            it("Sorts milestones by label in reverse order", () => {
                expect(wrapper.vm.sorted_milestones).toEqual([
                    milestone_3,
                    milestone_2,
                    milestone_1,
                ]);
            });
        });

        describe("onMilestoneSelected()", () => {
            beforeEach(() => {
                const event = {
                    target: {
                        value: "1",
                    },
                };
                wrapper.vm.onMilestoneSelected(event);
            });

            it("Emits change event with a milestone corresponding to id given by event value", () => {
                expect(wrapper.emitted().change[0]).toEqual([{ id: 1, label: "first milestone" }]);
            });
        });
    });

    describe("when no milestone", () => {
        beforeEach(() => {
            wrapper.setProps({ milestones: [] });
        });

        it("does not show any milestone", () => {
            expect(wrapper.find("milestone_selector").exists()).toBeFalsy();
        });

        it("shows information message", () => {
            expect(wrapper.find('[data-test-type="empty-milestones"]').exists()).toBeTruthy();
        });
    });
});
