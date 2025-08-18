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
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import * as rest_querier from "../../api/rest-querier";
import NewBaselineModal from "./NewBaselineModal.vue";
import MilestonesSelect from "./MilestonesSelect.vue";
import MilestonesSelectSkeleton from "./MilestonesSelectSkeleton.vue";

jest.useFakeTimers();

describe("NewBaselineModal", () => {
    let createBaseline, wrapper, hide_modal_mock, notify_mock, load_mock, a_baseline, a_milestone;

    const error_message_selector = '[data-test-type="error-message"]';
    const cancel_selector = '[data-test-action="cancel"]';

    let getOpenMilestonesResolve, getOpenMilestonesReject;

    beforeEach(async () => {
        hide_modal_mock = jest.fn();
        notify_mock = jest.fn();
        load_mock = jest.fn();

        a_milestone = { id: 1 };
        a_baseline = {
            id: 1001,
            name: "Baseline label",
            artifact_id: 9,
            snapshot_date: "2019-03-22T10:01:48+00:00",
            author_id: 3,
        };

        jest.spyOn(rest_querier, "getOpenMilestones").mockReturnValue(
            new Promise((resolve, reject) => {
                getOpenMilestonesResolve = resolve;
                getOpenMilestonesReject = reject;
            }),
        );

        createBaseline = jest
            .spyOn(rest_querier, "createBaseline")
            .mockReturnValue(Promise.resolve(a_baseline));

        wrapper = shallowMount(NewBaselineModal, {
            props: { project_id: 1 },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        dialog_interface: {
                            namespaced: true,
                            mutations: {
                                hideModal: hide_modal_mock,
                                notify: notify_mock,
                            },
                        },
                        baselines: {
                            namespaced: true,
                            actions: {
                                load: load_mock,
                            },
                        },
                    },
                }),
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
            expect(wrapper.findComponent(MilestonesSelect).props().milestones).toStrictEqual([
                a_milestone,
            ]);
        });
    });

    describe("saveBaseline()", () => {
        let createBaselineResolve, createBaselineReject;
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
            expect(wrapper.get(cancel_selector).attributes()).toHaveProperty("disabled");
            expect(wrapper.get('[data-test-action="submit"]').attributes()).toHaveProperty(
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
                expect(notify_mock).toHaveBeenCalled();
            });
            it("reloads all baselines", () => {
                expect(load_mock).toHaveBeenCalled();
            });
            it("hides modal", () => {
                expect(hide_modal_mock).toHaveBeenCalled();
            });
        });
    });
});
