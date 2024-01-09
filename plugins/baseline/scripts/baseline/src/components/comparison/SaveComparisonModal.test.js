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
import VueRouter from "vue-router";
import { shallowMount } from "@vue/test-utils";
import * as rest_querier from "../../api/rest-querier";
import SaveComparisonModal from "./SaveComparisonModal.vue";
import store_options from "../../store/store_options";
import { createStoreMock } from "../../support/store-wrapper.test-helper";
import { createLocalVueForTests } from "../../support/local-vue";

describe("SaveComparisonModal", () => {
    const error_message_selector = '[data-test-type="error-message"]';
    let createComparison, $store, wrapper, router;

    beforeEach(async () => {
        createComparison = jest.spyOn(rest_querier, "createComparison");
        router = new VueRouter({
            mode: "abstract", // Do not use hash or history that is shared between tests
            routes: [
                {
                    path: "/",
                },
                {
                    path: "/path/to/comparison",
                    name: "ComparisonPage",
                },
            ],
        });

        $store = createStoreMock(store_options);

        wrapper = shallowMount(SaveComparisonModal, {
            propsData: { base_baseline_id: 1, compared_to_baseline_id: 2 },
            localVue: await createLocalVueForTests(),
            router,
            mocks: { $store },
        });
    });

    it("does not show error message", () => {
        expect(wrapper.find(error_message_selector).exists()).toBeFalsy();
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
            await Vue.nextTick();
            expect(wrapper.find('[data-test-type="spinner"]').exists()).toBeTruthy();
        });

        describe("when createComparison() fail", () => {
            it("shows an error message", async () => {
                createComparisonReject("rejection");
                await wrapper.vm.saveComparison();
                expect(wrapper.find(error_message_selector).exists()).toBeTruthy();
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
                await wrapper.vm.saveComparison();
                await Vue.nextTick();
            });

            it("Navigates to comparison page", () => {
                expect(router.currentRoute.name).toBe("ComparisonPage");
                expect(router.currentRoute.params).toStrictEqual({ comparison_id: 10 });
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
