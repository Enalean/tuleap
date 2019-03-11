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
import { restore, rewire$getOpenMilestones, rewire$createBaseline } from "../api/rest-querier";
import NewBaselineModal from "./NewBaselineModal.vue";
import MilestoneList from "./NewBaselineMilestoneSelect.vue";
import MilestoneListSkeleton from "./MilestoneListSkeleton.vue";

describe("NewBaselineModal", () => {
    const error_message_selector = '[data-test-type="error-message"]';
    const information_message_selector = '[data-test-type="information_message"]';
    const spinner_selector = '[data-test-type="spinner"]';

    let getOpenMilestones;
    let createBaseline;
    let wrapper;

    const a_milestone = { id: 1, label: "release one" };
    const a_baseline = {
        id: 1,
        name: "My first baseline",
        milestone_id: 3,
        author_id: 2,
        creation_date: 12344567
    };

    beforeEach(() => {
        getOpenMilestones = jasmine.createSpy("getOpenMilestones");
        getOpenMilestones.and.returnValue(Promise.resolve([a_milestone]));
        rewire$getOpenMilestones(getOpenMilestones);

        createBaseline = jasmine.createSpy("createBaseline");
        createBaseline.and.returnValue(Promise.resolve(a_baseline));
        rewire$createBaseline(createBaseline);

        wrapper = shallowMount(NewBaselineModal, {
            localVue,
            propsData: { project_id: 1 }
        });
    });

    afterEach(restore);

    describe("reload()", () => {
        beforeEach(() => {
            wrapper.setData({
                name: "My baseline",
                milestone: a_milestone
            });
            wrapper.vm.reload();
        });

        it("resets inputs", () => {
            expect(wrapper.vm.name).toBeNull();
            expect(wrapper.vm.milestone).toBeNull();
        });

        it("Fetches milestones", () => {
            expect(getOpenMilestones).toHaveBeenCalled();
        });
    });

    describe("fetchMilestones()", () => {
        let getOpenMilestonesResolve;
        let getOpenMilestonesReject;

        beforeEach(() => {
            getOpenMilestones.and.returnValue(
                new Promise((resolve, reject) => {
                    getOpenMilestonesResolve = resolve;
                    getOpenMilestonesReject = reject;
                })
            );
            wrapper.vm.fetchMilestones();
        });

        it("shows skeleton", () => {
            expect(wrapper.contains(MilestoneListSkeleton)).toBeTruthy();
        });

        describe("when getOpenMilestones() fail", () => {
            beforeEach(async () => {
                getOpenMilestonesReject("rejection");
                await Vue.nextTick();
            });

            it("shows error message", () => {
                expect(wrapper.contains(error_message_selector)).toBeTruthy();
            });

            it("shows information message", () => {
                expect(wrapper.contains(information_message_selector)).toBeTruthy();
            });

            it("does not show skeleton", () => {
                expect(wrapper.contains(MilestoneListSkeleton)).toBeFalsy();
            });
        });

        describe("when getOpenMilestones() is successful", () => {
            beforeEach(async () => {
                getOpenMilestonesResolve([a_milestone]);
                await Vue.nextTick();
            });

            it("does not show error message", () => {
                expect(wrapper.contains(error_message_selector)).toBeFalsy();
            });

            it("does not show skeleton", () => {
                expect(wrapper.contains(MilestoneListSkeleton)).toBeFalsy();
            });

            it("shows a list of milestones", () => {
                expect(wrapper.contains(MilestoneList)).toBeTruthy();
            });

            it("passes milestones returned by getOpenMilestones() to MilestoneList", () => {
                expect(wrapper.find(MilestoneList).props().milestones).toEqual([a_milestone]);
            });
        });
    });

    describe("saveBaseline()", () => {
        let createBaselineResolve;
        let createBaselineReject;

        beforeEach(() => {
            createBaseline.and.returnValue(
                new Promise((resolve, reject) => {
                    createBaselineResolve = resolve;
                    createBaselineReject = reject;
                })
            );
            wrapper.vm.saveBaseline();
        });

        it("shows spinner", () => {
            expect(wrapper.contains(spinner_selector)).toBeTruthy();
        });

        describe("when createBaseline() fail", () => {
            beforeEach(async () => {
                createBaselineReject("rejection");
                await Vue.nextTick();
            });

            it("shows an error message", () => {
                expect(wrapper.find(error_message_selector).text).not.toBe(null);
            });
        });

        describe("when createBaseline() is successful", () => {
            beforeEach(async () => {
                createBaselineResolve(a_baseline);
                await Vue.nextTick();
            });

            it("sends event", () => {
                expect(wrapper.emitted().created).toBeTruthy();
            });
        });
    });
});
