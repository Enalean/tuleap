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
import { createLocalVue, shallowMount } from "@vue/test-utils";
import * as rest_querier from "../../api/rest-querier";
import SaveComparisonModal from "./SaveComparisonModal.vue";
import store_options from "../../store/store_options";
import { createStoreMock } from "../../support/store-wrapper.test-helper";
import GettextPlugin from "vue-gettext";
import { create } from "../../support/factories";

describe("SaveComparisonModal", () => {
    const error_message_selector = '[data-test-type="error-message"]';

    let createComparison;
    let $router;
    let $store;
    let wrapper;

    beforeEach(() => {
        createComparison = jest.spyOn(rest_querier, "createComparison");

        $store = createStoreMock(store_options);
        $router = { push: jest.fn() };

        const localVue = createLocalVue();
        localVue.use(GettextPlugin, {
            translations: {},
            silent: true,
        });

        wrapper = shallowMount(SaveComparisonModal, {
            propsData: { base_baseline_id: 1, compared_to_baseline_id: 2 },
            localVue,
            mocks: { $store, $router },
        });
    });

    it("does not show error message", () => {
        expect(wrapper.find(error_message_selector).exists()).toBeFalsy();
    });

    describe("saveComparison()", () => {
        let createComparisonResolve;
        let createComparisonReject;

        beforeEach(() => {
            createComparison.mockReturnValue(
                new Promise((resolve, reject) => {
                    createComparisonResolve = resolve;
                    createComparisonReject = reject;
                }),
            );
            wrapper.vm.saveComparison();
        });

        it("shows spinner", () => {
            expect(wrapper.find('[data-test-type="spinner"]').exists()).toBeTruthy();
        });

        describe("when createComparison() fail", () => {
            beforeEach(async () => {
                createComparisonReject("rejection");
                await Vue.nextTick();
            });

            it("shows an error message", () => {
                expect(wrapper.find(error_message_selector).exists()).toBeTruthy();
            });
        });

        describe("when createComparison() is successful", () => {
            beforeEach(async () => {
                createComparisonResolve(create("comparison", "saved", { id: 10 }));
                await Vue.nextTick();
            });

            it("Navigates to comparison page", () => {
                expect($router.push).toHaveBeenCalledWith({
                    name: "ComparisonPage",
                    params: {
                        comparison_id: 10,
                    },
                });
            });
            it("notify user with successful message", () => {
                expect($store.commit).toHaveBeenCalledWith(
                    "dialog_interface/notify",
                    expect.objectContaining({ class: "success" }),
                );
            });
            it("hides modal", () => {
                expect($store.commit).toHaveBeenCalledWith("dialog_interface/hideModal");
            });
        });
    });
});
