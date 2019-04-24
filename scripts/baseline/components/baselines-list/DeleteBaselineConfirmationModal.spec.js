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

import { mount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import { createStoreMock } from "../../support/store-wrapper.spec-helper.js";
import store_options from "../../store/store_options";
import DeleteBaselineConfirmationModal from "./DeleteBaselineConfirmationModal.vue";
import { create } from "../../support/factories";
import { restore, rewire$deleteBaseline } from "../../api/rest-querier";

describe("DeleteBaselineConfirmationModal", () => {
    const confirm_selector = '[data-test-action="confirm"]';
    const spinner_selector = '[data-test-type="spinner"]';

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

        wrapper = mount(DeleteBaselineConfirmationModal, {
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

    it("does not show spinner", () => {
        expect(wrapper.contains(spinner_selector)).toBeFalsy();
    });
    it("enables confirm button", () => {
        expect(wrapper.find(confirm_selector).attributes().disabled).toBeUndefined();
    });

    describe("when confirming", () => {
        beforeEach(async () => {
            wrapper.find(confirm_selector).trigger("click");
            await wrapper.vm.$nextTick();
        });

        it("shows spinner", () => {
            expect(wrapper.contains(spinner_selector)).toBeTruthy();
        });
        it("disables confirm button", () => {
            expect(wrapper.find(confirm_selector).attributes().disabled).toEqual("disabled");
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
            it("does not show spinner any more", () => {
                expect(wrapper.contains(spinner_selector)).toBeFalsy();
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
            it("does not show spinner any more", () => {
                expect(wrapper.contains(spinner_selector)).toBeFalsy();
            });
            it("enables confirm button", () => {
                expect(wrapper.find(confirm_selector).attributes().disabled).toBeUndefined();
            });
        });
    });
});
