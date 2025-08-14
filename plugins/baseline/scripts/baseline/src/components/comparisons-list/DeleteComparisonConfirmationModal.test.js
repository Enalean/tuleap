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
 */

import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import DeleteComparisonConfirmationModal from "./DeleteComparisonConfirmationModal.vue";
import * as rest_querier from "../../api/rest-querier";

jest.useFakeTimers();

describe("DeleteComparisonConfirmationModal", () => {
    let deleteComparison,
        deleteComparisonResolve,
        deleteComparisonReject,
        comparison,
        wrapper,
        hide_modal_mock,
        notify_mock,
        delete_mock;

    beforeEach(() => {
        hide_modal_mock = jest.fn();
        notify_mock = jest.fn();
        delete_mock = jest.fn();

        deleteComparison = jest.spyOn(rest_querier, "deleteComparison").mockReturnValue(
            new Promise((resolve, reject) => {
                deleteComparisonResolve = resolve;
                deleteComparisonReject = reject;
            }),
        );

        comparison = { id: 1 };

        wrapper = shallowMount(DeleteComparisonConfirmationModal, {
            props: {
                comparison,
                base_baseline: {
                    id: 1001,
                    name: "Baseline label",
                    artifact_id: 9,
                    snapshot_date: "2019-03-22T10:01:48+00:00",
                    author_id: 3,
                },
                compared_to_baseline: {
                    id: 1001,
                    name: "Baseline label",
                    artifact_id: 9,
                    snapshot_date: "2019-03-22T10:01:48+00:00",
                    author_id: 3,
                },
            },
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
                        comparisons: {
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

        it("deletes comparison", () => {
            expect(deleteComparison).toHaveBeenCalledWith(1);
        });

        describe("and deletion is successful", () => {
            beforeEach(async () => {
                deleteComparisonResolve();
                await jest.runOnlyPendingTimersAsync();
            });
            it("deletes comparison in store", () => {
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
                deleteComparisonReject();
                await jest.runOnlyPendingTimersAsync();
            });
            it("does not delete comparison in store", () => {
                expect(delete_mock).not.toHaveBeenCalled();
            });
            it("does not notify user", () => {
                expect(notify_mock).not.toHaveBeenCalled();
            });
        });
    });
});
