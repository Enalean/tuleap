/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ShowErrorDetails from "./ShowErrorDetails.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import type { ErrorState } from "../../../store/error/module";
import { nextTick } from "vue";

describe("ShowErrorDetails", () => {
    let show_error_details_factory: () => VueWrapper<InstanceType<typeof ShowErrorDetails>>,
        folder_loading_error: "Error during folder load.",
        document_loading_error: "Error during folder load.",
        document_lock_error: "Error during lock document.";

    describe("folder has a loading error", () => {
        beforeEach(() => {
            show_error_details_factory = (): VueWrapper<InstanceType<typeof ShowErrorDetails>> => {
                return shallowMount(ShowErrorDetails, {
                    props: {},
                    global: {
                        ...getGlobalTestOptions({
                            modules: {
                                error: {
                                    state: {
                                        folder_loading_error,
                                        document_loading_error,
                                        document_lock_error,
                                        has_folder_loading_error: true,
                                    } as unknown as ErrorState,
                                    getters: {
                                        has_any_loading_error: () => true,
                                    },
                                    namespaced: true,
                                },
                            },
                        }),
                    },
                });
            };
        });
        it(`Given route fails with an error with content and is_more_shown is true
            When we display the error
            Then the original error message is displayed in a info`, async () => {
            const wrapper = show_error_details_factory();
            expect(
                wrapper.find("[data-test=error-details-show-more-button]").exists(),
            ).toBeTruthy();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await nextTick();
            expect(wrapper.find("[data-test=show-more-error-message]").exists()).toBeTruthy();
        });

        it(`Given route fails with an error with content and is_more_shown is false
            When we display the error
            Then a button enables use to see the full error message`, () => {
            const wrapper = show_error_details_factory();
            expect(wrapper.find("[data-test=show-more-error-message]").exists()).toBeFalsy();
            expect(
                wrapper.find("[data-test=error-details-show-more-button]").exists(),
            ).toBeTruthy();
        });

        it(`Given error concerns a folder and is_more_shown is true
            When we display the error
            Then the message displayed is the folder one`, async () => {
            const wrapper = show_error_details_factory();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await nextTick();
            expect(wrapper.vm.error_message).toBe(folder_loading_error);
        });
    });

    describe("item has a loading error", () => {
        beforeEach(() => {
            show_error_details_factory = (): VueWrapper<InstanceType<typeof ShowErrorDetails>> => {
                return shallowMount(ShowErrorDetails, {
                    props: {},
                    global: {
                        ...getGlobalTestOptions({
                            modules: {
                                error: {
                                    state: {
                                        has_document_loading_error: true,
                                        document_lock_error,
                                    } as unknown as ErrorState,
                                    getters: {
                                        has_any_loading_error: () => true,
                                    },
                                    namespaced: true,
                                },
                            },
                        }),
                    },
                });
            };
        });

        it(`Given error concerns an item and is_more_shown is true
        When we display the error
        Then the message displayed is the item one`, async () => {
            const wrapper = show_error_details_factory();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await nextTick();
            expect(wrapper.vm.error_message).toBe(document_loading_error);
        });
    });

    describe("item has a lock error", () => {
        beforeEach(() => {
            show_error_details_factory = (): VueWrapper<InstanceType<typeof ShowErrorDetails>> => {
                return shallowMount(ShowErrorDetails, {
                    props: {},
                    global: {
                        ...getGlobalTestOptions({
                            modules: {
                                error: {
                                    state: {
                                        has_document_lock_error: true,
                                        document_lock_error,
                                    } as unknown as ErrorState,
                                    getters: {
                                        has_any_loading_error: () => true,
                                    },
                                    namespaced: true,
                                },
                            },
                        }),
                    },
                });
            };
        });
        it(`Given route fails with an error with content and is_more_shown is true
            When we display the error
            Then the original error message is displayed in a info`, async () => {
            const wrapper = show_error_details_factory();
            expect(
                wrapper.find("[data-test=error-details-show-more-button]").exists(),
            ).toBeTruthy();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await nextTick();
            expect(wrapper.find("[data-test=show-more-error-message]").exists()).toBeTruthy();
        });

        it(`Given route fails with an error with content and is_more_shown is false
            When we display the error
            Then a button enables use to see the full error message`, () => {
            const wrapper = show_error_details_factory();
            expect(wrapper.find("[data-test=show-more-error-message]").exists()).toBeFalsy();
            expect(
                wrapper.find("[data-test=error-details-show-more-button]").exists(),
            ).toBeTruthy();
        });

        it(`Given error concerns a lock and is_more_shown is true
            When we display the error
            Then the message displayed is the folder one`, async () => {
            const wrapper = show_error_details_factory();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await nextTick();
            expect(wrapper.vm.error_message).toBe(document_lock_error);
        });
    });
});
