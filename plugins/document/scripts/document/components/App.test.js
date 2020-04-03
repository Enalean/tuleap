/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
import App from "./App.vue";

import localVue from "../helpers/local-vue.js";
import { createStoreMock } from "../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import VueRouter from "vue-router";
import DocumentBreadcrumb from "./Breadcrumb/DocumentBreadcrumb.vue";
import PermissionError from "./Folder/Error/PermissionError.vue";
import ItemPermissionError from "./Folder/Error/ItemPermissionError.vue";
import LoadingError from "./Folder/Error/LoadingError.vue";
import SwitchToOldUI from "./Folder/SwitchToOldUI.vue";

describe("App", () => {
    let factory, state, store, store_options, router, default_prop;
    beforeEach(() => {
        router = new VueRouter({});

        default_prop = {
            user_id: 1,
            project_id: 101,
            user_is_admin: true,
            user_can_create_wiki: false,
            date_time_format: "Ymd",
            max_files_dragndrop: 5,
            max_size_upload: 10000,
            embedded_are_allowed: true,
            is_deletion_allowed: true,
            is_item_status_metadata_used: false,
            is_obsolescence_date_metadata_used: false,
            csrf_token: "challenge_value",
            csrf_token_name: "challenge_name",
        };

        factory = (state = {}, props = {}) => {
            store_options = {
                state,
            };
            store = createStoreMock(store_options);

            // eslint-disable-next-line jest/prefer-spy-on
            store.watch = jest.fn();
            jest.spyOn(store, "watch").mockImplementation((watchFunction, callback) =>
                callback(true)
            );

            return shallowMount(App, {
                localVue,
                propsData: props,
                mocks: { $store: store },
                router,
            });
        };
    });

    it(`Displays folder permission error if user can't access to a folder`, () => {
        state = {
            error: {
                has_folder_permission_error: true,
                has_folder_loading_error: false,
                has_document_permission_error: false,
                has_document_loading_error: false,
                has_document_lock_error: false,
            },
        };

        const wrapper = factory(state, default_prop);

        expect(wrapper.find(DocumentBreadcrumb).exists()).toBeFalsy();
        expect(wrapper.find(PermissionError).exists()).toBeTruthy();
        expect(wrapper.find(ItemPermissionError).exists()).toBeFalsy();
        expect(wrapper.find(LoadingError).exists()).toBeFalsy();
    });

    it(`Displays loading error if folder fails to load itself`, () => {
        state = {
            error: {
                has_folder_permission_error: false,
                has_folder_loading_error: true,
                has_document_permission_error: false,
                has_document_loading_error: false,
                has_document_lock_error: false,
            },
        };

        const wrapper = factory(state, default_prop);

        expect(wrapper.find(DocumentBreadcrumb).exists()).toBeTruthy();
        expect(wrapper.find(PermissionError).exists()).toBeFalsy();
        expect(wrapper.find(ItemPermissionError).exists()).toBeFalsy();
        expect(wrapper.find(LoadingError).exists()).toBeTruthy();
    });

    it(`Displays item permission error if user can't access to a document`, () => {
        state = {
            error: {
                has_folder_permission_error: false,
                has_folder_loading_error: false,
                has_document_permission_error: true,
                has_document_loading_error: false,
                has_document_lock_error: false,
            },
        };

        const wrapper = factory(state, default_prop);

        expect(wrapper.find(DocumentBreadcrumb).exists()).toBeTruthy();
        expect(wrapper.find(PermissionError).exists()).toBeFalsy();
        expect(wrapper.find(ItemPermissionError).exists()).toBeTruthy();
        expect(wrapper.find(LoadingError).exists()).toBeFalsy();
    });

    it(`Displays item loading error if document load fails`, () => {
        state = {
            error: {
                has_folder_permission_error: false,
                has_folder_loading_error: false,
                has_document_permission_error: false,
                has_document_loading_error: true,
                has_document_lock_error: false,
            },
        };

        const wrapper = factory(state, default_prop);

        expect(wrapper.find(DocumentBreadcrumb).exists()).toBeTruthy();
        expect(wrapper.find(PermissionError).exists()).toBeFalsy();
        expect(wrapper.find(ItemPermissionError).exists()).toBeFalsy();
        expect(wrapper.find(LoadingError).exists()).toBeTruthy();
    });

    it(`Displays item loading error if document is locked`, () => {
        state = {
            error: {
                has_folder_permission_error: false,
                has_folder_loading_error: false,
                has_document_permission_error: false,
                has_document_loading_error: false,
                has_document_lock_error: true,
            },
        };

        const wrapper = factory(state, default_prop);

        expect(wrapper.find(DocumentBreadcrumb).exists()).toBeTruthy();
        expect(wrapper.find(PermissionError).exists()).toBeFalsy();
        expect(wrapper.find(ItemPermissionError).exists()).toBeFalsy();
        expect(wrapper.find(LoadingError).exists()).toBeTruthy();
    });

    it(`Does not display link back to old UI for anonymous`, () => {
        const props = {
            user_id: 0,
            project_id: 101,
            user_is_admin: true,
            user_can_create_wiki: false,
            date_time_format: "Ymd",
            max_files_dragndrop: 5,
            max_size_upload: 10000,
            embedded_are_allowed: true,
            is_deletion_allowed: true,
            is_item_status_metadata_used: false,
            is_obsolescence_date_metadata_used: false,
            csrf_token: "challenge_value",
            csrf_token_name: "challenge_name",
        };

        state = {
            error: {
                has_folder_permission_error: false,
                has_folder_loading_error: false,
                has_document_permission_error: false,
                has_document_loading_error: false,
                has_document_lock_error: false,
            },
        };

        const wrapper = factory(state, props);

        expect(wrapper.find(SwitchToOldUI).exists()).toBeFalsy();
    });

    it(`Displays a switch back link for connected users`, () => {
        state = {
            error: {
                has_folder_permission_error: false,
                has_folder_loading_error: false,
                has_document_permission_error: false,
                has_document_loading_error: false,
                has_document_lock_error: false,
            },
        };

        const wrapper = factory(state, default_prop);

        expect(wrapper.find(SwitchToOldUI).exists()).toBeTruthy();
    });
});
