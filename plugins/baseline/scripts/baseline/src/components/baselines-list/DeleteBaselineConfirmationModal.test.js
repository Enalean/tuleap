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
import { createStoreMock } from "../../support/store-wrapper.test-helper.js";
import store_options from "../../store/store_options";
import DeleteBaselineConfirmationModal from "./DeleteBaselineConfirmationModal.vue";
import * as rest_querier from "../../api/rest-querier";

describe("DeleteBaselineConfirmationModal", () => {
    let deleteBaseline, deleteBaselineResolve, deleteBaselineReject;

    const baseline = { id: 1, name: "Baseline" };
    let $store, wrapper;

    beforeEach(async () => {
        deleteBaseline = jest.spyOn(rest_querier, "deleteBaseline").mockReturnValue(
            new Promise((resolve, reject) => {
                deleteBaselineResolve = resolve;
                deleteBaselineReject = reject;
            }),
        );

        $store = createStoreMock(store_options);

        wrapper = shallowMount(DeleteBaselineConfirmationModal, {
            propsData: {
                baseline,
            },
            localVue: await createLocalVueForTests(),
            mocks: {
                $store,
            },
        });
    });

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
                    expect.any(Object),
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
                    expect.any(Object),
                );
            });
            it("does not notify user", () => {
                expect($store.commit).not.toHaveBeenCalledWith(
                    "dialog_interface/notify",
                    expect.any(Object),
                );
            });
        });
    });
});
