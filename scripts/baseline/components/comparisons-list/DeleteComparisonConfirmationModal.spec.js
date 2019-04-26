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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import { createStoreMock } from "../../support/store-wrapper.spec-helper.js";
import store_options from "../../store/store_options";
import DeleteComparisonConfirmationModal from "./DeleteComparisonConfirmationModal.vue";
import { create } from "../../support/factories";
import { restore, rewire$deleteComparison } from "../../api/rest-querier";

describe("DeleteComparisonConfirmationModal", () => {
    let deleteComparison;
    let deleteComparisonResolve;
    let deleteComparisonReject;

    const comparison = create("comparison", "saved", { id: 1 });

    let $store;
    let wrapper;

    beforeEach(() => {
        deleteComparison = jasmine.createSpy("deleteComparison");
        deleteComparison.and.returnValue(
            new Promise((resolve, reject) => {
                deleteComparisonResolve = resolve;
                deleteComparisonReject = reject;
            })
        );
        rewire$deleteComparison(deleteComparison);

        $store = createStoreMock(store_options);

        wrapper = shallowMount(DeleteComparisonConfirmationModal, {
            propsData: {
                comparison
            },
            localVue,
            mocks: {
                $store
            }
        });
    });

    afterEach(restore);

    describe("when confirming", () => {
        beforeEach(() => {
            wrapper.vm.confirm();
        });

        it("deletes comparison", () => {
            expect(deleteComparison).toHaveBeenCalledWith(1);
        });

        describe("and deletion is successful", () => {
            beforeEach(async () => {
                deleteComparisonResolve();
                await wrapper.vm.$nextTick();
            });
            it("deletes comparison in store", () => {
                expect($store.commit).toHaveBeenCalledWith("comparisons/delete", comparison);
            });
            it("notifies user", () => {
                expect($store.commit).toHaveBeenCalledWith(
                    "dialog_interface/notify",
                    jasmine.any(Object)
                );
            });
            it("hides modal", () => {
                expect($store.commit).toHaveBeenCalledWith("dialog_interface/hideModal");
            });
        });

        describe("and deletion failed", () => {
            beforeEach(async () => {
                deleteComparisonReject();
                await wrapper.vm.$nextTick();
            });
            it("does not delete comparison in store", () => {
                expect($store.commit).not.toHaveBeenCalledWith(
                    "comparisons/delete",
                    jasmine.any(Object)
                );
            });
            it("does not notify user", () => {
                expect($store.commit).not.toHaveBeenCalledWith(
                    "dialog_interface/notify",
                    jasmine.any(Object)
                );
            });
        });
    });
});
