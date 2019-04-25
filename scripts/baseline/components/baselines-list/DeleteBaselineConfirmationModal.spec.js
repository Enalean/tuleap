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
 *
 */

import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import { createStoreMock } from "../../support/store-wrapper.spec-helper.js";
import store_options from "../../store/store_options";
import DeleteBaselineConfirmationModal from "./DeleteBaselineConfirmationModal.vue";
import { create } from "../../support/factories";
import { restore, rewire$deleteBaseline } from "../../api/rest-querier";

describe("DeleteBaselineConfirmationModal", () => {
    let deleteBaseline;
    let deleteBaselineResolve;
    let deleteBaselineReject;

    const baseline = create("baseline", { id: 1 });

    let $store;
    let wrapper;

    beforeEach(() => {
        deleteBaseline = jasmine.createSpy("deleteBaseline");
        deleteBaseline.and.returnValue(
            new Promise((resolve, reject) => {
                deleteBaselineResolve = resolve;
                deleteBaselineReject = reject;
            })
        );
        rewire$deleteBaseline(deleteBaseline);

        $store = createStoreMock(store_options);

        wrapper = shallowMount(DeleteBaselineConfirmationModal, {
            propsData: {
                baseline
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

        it("deletes baseline", () => {
            expect(deleteBaseline).toHaveBeenCalledWith(1);
        });

        describe("and deletion is successful", () => {
            beforeEach(async () => {
                deleteBaselineResolve();
                await wrapper.vm.$nextTick();
            });
            it("deletes baseline in store", () => {
                expect($store.commit).toHaveBeenCalledWith("baselines/delete", baseline);
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
                deleteBaselineReject();
                await wrapper.vm.$nextTick();
            });
            it("does not delete baseline in store", () => {
                expect($store.commit).not.toHaveBeenCalledWith(
                    "baselines/delete",
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
