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
import * as rest_querier from "../../api/rest-querier";
import SaveComparisonModal from "./SaveComparisonModal.vue";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";

jest.useFakeTimers();

const mockRoute = {};

const mockRouter = {
    push: jest.fn(),
};

describe("SaveComparisonModal", () => {
    let createComparison, wrapper, notify_mock, hide_modal_mock;

    beforeEach(() => {
        notify_mock = jest.fn();
        hide_modal_mock = jest.fn();

        createComparison = jest.spyOn(rest_querier, "createComparison");

        wrapper = shallowMount(SaveComparisonModal, {
            props: { base_baseline_id: 1, compared_to_baseline_id: 2 },
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
                    },
                }),
                mocks: {
                    $route: mockRoute,
                    $router: mockRouter,
                },
            },
        });
    });

    it("does not show error message", () => {
        expect(wrapper.find("[data-test-type=error-message]").exists()).toBeFalsy();
    });

    describe("saveComparison()", () => {
        let createComparisonResolve, createComparisonReject;

        beforeEach(() => {
            createComparison.mockReturnValue(
                new Promise((resolve, reject) => {
                    createComparisonResolve = resolve;
                    createComparisonReject = reject;
                }),
            );
        });

        it("shows spinner", async () => {
            wrapper.vm.saveComparison();
            await jest.runOnlyPendingTimersAsync();
            expect(wrapper.find('[data-test-type="spinner"]').exists()).toBeTruthy();
        });

        describe("when createComparison() fail", () => {
            it("shows an error message", async () => {
                createComparisonReject("rejection");

                await wrapper.vm.saveComparison();

                expect(wrapper.find("[data-test-type=error-message]").exists()).toBeTruthy();
            });
        });

        describe("when createComparison() is successful", () => {
            beforeEach(async () => {
                createComparisonResolve({
                    id: 10,
                    name: null,
                    comment: null,
                    author_id: 1,
                    creation_date: "2019-03-22T10:01:48+00:00",
                });
                wrapper.vm.saveComparison();
                await jest.runOnlyPendingTimersAsync();
            });

            it("Navigates to comparison page", () => {
                expect(mockRouter.push).toHaveBeenCalledWith({
                    name: "ComparisonPage",
                    params: {
                        comparison_id: 10,
                    },
                });
            });
            it("notify user with successful message", () => {
                expect(notify_mock).toHaveBeenCalled();
            });
            it("hides modal", () => {
                expect(hide_modal_mock).toHaveBeenCalled();
            });
        });
    });
});
