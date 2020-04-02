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

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../helpers/local-vue.js";
import ShowErrorDetails from "./ShowErrorDetails.vue";

describe("ShowErrorDetails", () => {
    let show_error_details_factory,
        state,
        folder_loading_error,
        document_loading_error,
        store,
        document_lock_error;

    describe("folder has a loading error", () => {
        beforeEach(() => {
            folder_loading_error = "Error during folder load.";

            state = {
                error: {
                    has_folder_loading_error: true,
                    folder_loading_error,
                },
            };
            const store_options = {
                state,
                getters: {
                    "error/has_any_loading_error": true,
                },
            };

            store = createStoreMock(store_options);

            show_error_details_factory = (props = {}) => {
                return shallowMount(ShowErrorDetails, {
                    localVue,
                    propsData: { ...props },
                    mocks: { $store: store },
                });
            };
        });
        it(`Given route fails with an error with content and is_more_shown is true
            When we display the error
            Then the original error message is displayed in a info`, async () => {
            const wrapper = show_error_details_factory();
            expect(
                wrapper.find("[data-test=error-details-show-more-button]").exists()
            ).toBeTruthy();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await wrapper.vm.$nextTick();
            expect(wrapper.find("[data-test=show-more-error-message]").exists()).toBeTruthy();
        });

        it(`Given route fails with an error with content and is_more_shown is false
            When we display the error
            Then a button enables use to see the full error message`, () => {
            const wrapper = show_error_details_factory();
            expect(wrapper.find("[data-test=show-more-error-message]").exists()).toBeFalsy();
            expect(
                wrapper.find("[data-test=error-details-show-more-button]").exists()
            ).toBeTruthy();
        });

        it(`Given error concerns a folder and is_more_shown is true
            When we display the error
            Then the message displayed is the folder one`, async () => {
            const wrapper = show_error_details_factory();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await wrapper.vm.$nextTick();
            expect(wrapper.get("[data-test=show-more-error-message]").element.innerHTML).toBe(
                folder_loading_error
            );
        });
    });

    describe("item has a loading error", () => {
        beforeEach(() => {
            document_loading_error = "Error during folder load.";

            state = {
                error: {
                    has_document_loading_error: true,
                    document_loading_error: document_loading_error,
                },
            };
            const store_options = {
                state,
                getters: {
                    "error/has_any_loading_error": true,
                },
            };

            const store = createStoreMock(store_options);

            show_error_details_factory = (props = {}) => {
                return shallowMount(ShowErrorDetails, {
                    localVue,
                    propsData: { ...props },
                    mocks: { $store: store },
                });
            };
        });

        it(`Given error concerns an item and is_more_shown is true
        When we display the error
        Then the message displayed is the item one`, async () => {
            store.getters.has_any_loading_error = true;
            const wrapper = show_error_details_factory();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await wrapper.vm.$nextTick();
            expect(wrapper.get("[data-test=show-more-error-message]").element.innerHTML).toBe(
                document_loading_error
            );
        });
    });

    describe("item has a lock error", () => {
        beforeEach(() => {
            document_lock_error = "Error during lock document.";

            state = {
                error: {
                    has_document_lock_error: true,
                    document_lock_error,
                },
            };
            const store_options = {
                state,
                getters: {
                    "error/has_any_loading_error": true,
                },
            };

            store = createStoreMock(store_options);

            show_error_details_factory = (props = {}) => {
                return shallowMount(ShowErrorDetails, {
                    localVue,
                    propsData: { ...props },
                    mocks: { $store: store },
                });
            };
        });
        it(`Given route fails with an error with content and is_more_shown is true
            When we display the error
            Then the original error message is displayed in a info`, async () => {
            const wrapper = show_error_details_factory();
            expect(
                wrapper.find("[data-test=error-details-show-more-button]").exists()
            ).toBeTruthy();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await wrapper.vm.$nextTick();
            expect(wrapper.find("[data-test=show-more-error-message]").exists()).toBeTruthy();
        });

        it(`Given route fails with an error with content and is_more_shown is false
            When we display the error
            Then a button enables use to see the full error message`, () => {
            const wrapper = show_error_details_factory();
            expect(wrapper.find("[data-test=show-more-error-message]").exists()).toBeFalsy();
            expect(
                wrapper.find("[data-test=error-details-show-more-button]").exists()
            ).toBeTruthy();
        });

        it(`Given error concerns a lock and is_more_shown is true
            When we display the error
            Then the message displayed is the folder one`, async () => {
            const wrapper = show_error_details_factory();
            wrapper.get("[data-test=error-details-show-more-button]").trigger("click");
            await wrapper.vm.$nextTick();
            expect(wrapper.get("[data-test=show-more-error-message]").element.innerHTML).toBe(
                document_lock_error
            );
        });
    });
});
