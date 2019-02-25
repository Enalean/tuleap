/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
import { shallowMount } from "@vue/test-utils";
import localVue from "../support/local-vue.js";
import MilestoneList from "./NewBaselineMilestoneSelect.vue";

describe("NewBaselineMilestoneSelect", () => {
    const milestone_selector = '[data-test-type="milestone"]';

    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(MilestoneList, {
            localVue,
            propsData: {
                milestones: []
            },
            sync: false
        });
    });

    describe("when many milestones", () => {
        beforeEach(async () => {
            wrapper.setProps({
                milestones: [
                    { id: 1, label: "first milestone" },
                    { id: 2, label: "second milestone" },
                    { id: 3, label: "a milestone" }
                ]
            });
            await Vue.nextTick();
        });

        it("shows as many milestones as given", () => {
            let milestones = wrapper.findAll(milestone_selector);
            expect(milestones.length).toBe(3);
        });

        describe("sorted_milestones", () => {
            it("Sorts milestones by label", () => {
                expect(wrapper.vm.sorted_milestones).toEqual([
                    { id: 3, label: "a milestone" },
                    { id: 1, label: "first milestone" },
                    { id: 2, label: "second milestone" }
                ]);
            });
        });

        describe("onMilestoneSelected()", () => {
            beforeEach(() => {
                const event = {
                    target: {
                        value: "1"
                    }
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
            expect(wrapper.contains("milestone_selector")).toBeFalsy();
        });

        it("shows information message", () => {
            expect(wrapper.contains('[data-test-type="empty-milestones"]')).toBeTruthy();
        });
    });
});
