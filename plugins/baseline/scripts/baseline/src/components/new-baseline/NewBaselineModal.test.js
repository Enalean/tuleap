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

import { shallowMount } from "@vue/test-utils";
import { createLocalVueForTests } from "../../support/local-vue.ts";
import * as rest_querier from "../../api/rest-querier";
import NewBaselineModal from "./NewBaselineModal.vue";
import MilestonesSelect from "./MilestonesSelect.vue";
import MilestonesSelectSkeleton from "./MilestonesSelectSkeleton.vue";
import store_options from "../../store/store_options";
import { createStoreMock } from "../../support/store-wrapper.test-helper";

jest.useFakeTimers();

describe("NewBaselineModal", () => {
    const error_message_selector = '[data-test-type="error-message"]';
    const cancel_selector = '[data-test-action="cancel"]';

    let createBaseline, $store, wrapper;

    const a_milestone = { id: 1 };
    const a_baseline = {
        id: 1001,
        name: "Baseline label",
        artifact_id: 9,
        snapshot_date: "2019-03-22T10:01:48+00:00",
        author_id: 3,
    };
    let getOpenMilestonesResolve, getOpenMilestonesReject;

    beforeEach(async () => {
        jest.spyOn(rest_querier, "getOpenMilestones").mockReturnValue(
            new Promise((resolve, reject) => {
                getOpenMilestonesResolve = resolve;
                getOpenMilestonesReject = reject;
            }),
        );

        createBaseline = jest
            .spyOn(rest_querier, "createBaseline")
            .mockReturnValue(Promise.resolve(a_baseline));

        $store = createStoreMock(store_options);

        wrapper = shallowMount(NewBaselineModal, {
            propsData: { project_id: 1 },
            localVue: await createLocalVueForTests(),
            mocks: {
                $store,
            },
        });
        await jest.runOnlyPendingTimersAsync();
    });

    it("shows skeleton", () => {
        expect(wrapper.findComponent(MilestonesSelectSkeleton).exists()).toBeTruthy();
    });

    describe("when getOpenMilestones() fail", () => {
        beforeEach(async () => {
            getOpenMilestonesReject("rejection");
            await jest.runOnlyPendingTimersAsync();
        });

        it("shows error message", () => {
            expect(wrapper.find(error_message_selector).exists()).toBeTruthy();
        });

        it("shows information message", () => {
            expect(wrapper.find('[data-test-type="information_message"]').exists()).toBeTruthy();
        });

        it("does not show skeleton", () => {
            expect(wrapper.findComponent(MilestonesSelectSkeleton).exists()).toBeFalsy();
        });
    });

    describe("when getOpenMilestones() is successful", () => {
        beforeEach(async () => {
            getOpenMilestonesResolve([a_milestone]);
            await jest.runOnlyPendingTimersAsync();
        });

        it("does not show error message", () => {
            expect(wrapper.find(error_message_selector).exists()).toBeFalsy();
        });

        it("does not show skeleton", () => {
            expect(wrapper.findComponent(MilestonesSelectSkeleton).exists()).toBeFalsy();
        });

        it("shows a list of milestones", () => {
            expect(wrapper.findComponent(MilestonesSelect).exists()).toBeTruthy();
        });

        it("passes milestones returned by getOpenMilestones() to MilestoneList", () => {
            expect(wrapper.findComponent(MilestonesSelect).props().milestones).toEqual([
                a_milestone,
            ]);
        });
    });

    describe("saveBaseline()", () => {
        let createBaselineResolve;
        let createBaselineReject;

        beforeEach(() => {
            createBaseline.mockReturnValue(
                new Promise((resolve, reject) => {
                    createBaselineResolve = resolve;
                    createBaselineReject = reject;
                }),
            );
            wrapper.vm.saveBaseline();
        });

        it("shows spinner", () => {
            expect(wrapper.find('[data-test-type="spinner"]').exists()).toBeTruthy();
        });

        it("disables buttons", () => {
            expect(wrapper.get(cancel_selector).attributes("disabled")).toBe("disabled");
            expect(wrapper.get('[data-test-action="submit"]').attributes("disabled")).toBe(
                "disabled",
            );
        });

        describe("when createBaseline() fail", () => {
            beforeEach(async () => {
                createBaselineReject("rejection");
                await jest.runOnlyPendingTimersAsync();
            });

            it("shows an error message", () => {
                expect(wrapper.get(error_message_selector).text).not.toBeNull();
            });
            it("enables cancel buttons", () => {
                expect(wrapper.get(cancel_selector).attributes("disabled")).not.toBe("disabled");
            });
        });

        describe("when createBaseline() is successful", () => {
            beforeEach(async () => {
                createBaselineResolve(a_baseline);
                await jest.runOnlyPendingTimersAsync();
            });

            it("notify user with successful creation", () => {
                expect($store.commit).toHaveBeenCalledWith(
                    "dialog_interface/notify",
                    expect.any(Object),
                );
            });
            it("reloads all baselines", () => {
                expect($store.dispatch).toHaveBeenCalledWith("baselines/load", { project_id: 1 });
            });
            it("hides modal", () => {
                expect($store.commit).toHaveBeenCalledWith("dialog_interface/hideModal");
            });
        });
    });
});
