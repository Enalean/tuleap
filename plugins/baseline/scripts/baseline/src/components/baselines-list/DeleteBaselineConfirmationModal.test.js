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
import DeleteBaselineConfirmationModal from "./DeleteBaselineConfirmationModal.vue";
import * as rest_querier from "../../api/rest-querier";

jest.useFakeTimers();

describe("DeleteBaselineConfirmationModal", () => {
    let deleteBaseline,
        deleteBaselineResolve,
        deleteBaselineReject,
        notify_mock,
        hide_modal_mock,
        delete_mock,
        wrapper;

    beforeEach(() => {
        notify_mock = jest.fn();
        hide_modal_mock = jest.fn();
        delete_mock = jest.fn();

        deleteBaseline = jest.spyOn(rest_querier, "deleteBaseline").mockReturnValue(
            new Promise((resolve, reject) => {
                deleteBaselineResolve = resolve;
                deleteBaselineReject = reject;
            }),
        );

        const baseline = { id: 1, name: "Baseline" };

        wrapper = shallowMount(DeleteBaselineConfirmationModal, {
            props: { baseline },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        dialog_interface: {
                            namespaced: true,
                            mutations: {
                                notify: notify_mock,
                                hideModal: hide_modal_mock,
                            },
                        },
                        baselines: {
                            namespaced: true,
                            mutations: {
                                delete: delete_mock,
                            },
                        },
                    },
                }),
            },
            directives: {
                "dompurify-html": jest.fn(),
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
                await jest.runOnlyPendingTimersAsync();
            });
            it("deletes baseline in store", () => {
                expect(delete_mock).toHaveBeenCalled();
            });
            it("notifies user", () => {
                expect(notify_mock).toHaveBeenCalled();
            });
            it("hides modal", () => {
                expect(hide_modal_mock).toHaveBeenCalled();
            });
        });

        describe("and deletion failed", () => {
            beforeEach(async () => {
                deleteBaselineReject();
                await jest.runOnlyPendingTimersAsync();
            });
            it("does not delete baseline in store", () => {
                expect(delete_mock).not.toHaveBeenCalled();
            });
            it("does not notify user", () => {
                expect(notify_mock).not.toHaveBeenCalled();
            });
        });
    });
});
